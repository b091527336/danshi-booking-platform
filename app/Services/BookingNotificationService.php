<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingNotification;
use App\Models\LineRecipient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BookingNotificationService
{
    public function __construct(private readonly BookingNotificationTemplate $templates) {}

    public function enqueue(Booking $booking, string $event): void
    {
        if ($booking->starts_at->isPast()) {
            return;
        }
        $booking->load('organization','customer');
        $settings = array_merge(BookingNotificationTemplate::defaults(), $booking->organization->settings['notifications'] ?? []);
        $fingerprint = hash('sha256',json_encode([$booking->status,$booking->starts_at->toIso8601String(),$booking->ends_at?->toIso8601String(),$booking->service_name]));
        BookingNotification::where('booking_id',$booking->id)->where('status','pending')->where('fingerprint','!=',$fingerprint)
            ->update(['status'=>'superseded']);
        foreach (['staff','customer'] as $audience) {
            if (! $settings[$audience.'_enabled']) {
                continue;
            }
            BookingNotification::firstOrCreate(['booking_id'=>$booking->id,'audience'=>$audience,'fingerprint'=>$fingerprint], [
                'retry_key'=>(string) Str::uuid(), 'body'=>$this->templates->render($settings[$audience.'_template'],$booking,$event),
                'status'=>'pending','next_attempt_at'=>now(),
            ]);
        }
    }

    public function deliver(BookingNotification $notification): void
    {
        $booking = $notification->booking;
        if (! $booking || $notification->created_at->lt(now()->subHours(12)) || $booking->starts_at->isPast()) {
            $notification->update(['status'=>'expired']);
            return;
        }
        $settings = $booking->organization->settings['notifications'] ?? [];
        if (! ($settings[$notification->audience.'_enabled'] ?? false)) {
            $notification->update(['status'=>'disabled']);
            return;
        }
        $channel = $settings['channel'] ?? null;
        $token = $channel ? config('services.line.channels.'.$channel.'.token') : null;
        $recipient = LineRecipient::where('organization_id',$booking->organization_id)
            ->where('audience',$notification->audience)->where('channel',$channel)
            ->when($notification->audience==='customer',fn ($query) => $query->where('customer_id',$booking->customer_id),fn ($query) => $query->whereNull('customer_id'))
            ->first();
        if (! $token || ! $recipient) {
            $notification->update(['error_code'=>'LINE_NOT_CONNECTED','next_attempt_at'=>now()->addMinutes(5)]);
            return;
        }
        // LINE remembers retry keys for 24 hours. All retries expire within 12 hours.
        $notification->increment('attempts');
        try {
            $response = Http::withToken($token)->acceptJson()->timeout(15)
                ->withHeaders(['X-Line-Retry-Key'=>$notification->retry_key])
                ->post('https://api.line.me/v2/bot/message/push',[
                    'to'=>$recipient->line_id, 'messages'=>[['type'=>'text','text'=>$notification->body]],
                ]);
            if ($response->successful() || ($response->status()===409 && $response->header('X-Line-Accepted-Request-Id'))) {
                $notification->update(['status'=>'sent','sent_at'=>now(),'error_code'=>null]);
                return;
            }
            $retryable = $response->status()===429 || $response->serverError();
            $notification->update([
                'status'=>$retryable && $notification->attempts<8 ? 'pending' : 'failed',
                'error_code'=>'LINE_HTTP_'.$response->status(), 'next_attempt_at'=>now()->addMinutes(min(60,2 ** $notification->attempts)),
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            $notification->update(['status'=>$notification->attempts<8 ? 'pending':'failed','error_code'=>'LINE_CONNECTION_ERROR','next_attempt_at'=>now()->addMinutes(5)]);
        }
    }
}
