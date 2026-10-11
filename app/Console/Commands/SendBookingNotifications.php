<?php

namespace App\Console\Commands;

use App\Models\BookingNotification;
use App\Services\BookingNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendBookingNotifications extends Command
{
    protected $signature = 'dbp:send-notifications';
    protected $description = 'Send pending booking notifications without interrupting booking synchronization';

    public function handle(BookingNotificationService $service): int
    {
        BookingNotification::where('status','pending')->where('next_attempt_at','<=',now())->orderBy('id')->limit(100)->get()
            ->each(function ($notification) use ($service) {
                Cache::lock('booking-notification:'.$notification->id,60)->get(function () use ($notification,$service) {
                    $notification->refresh();
                    if ($notification->status==='pending' && $notification->next_attempt_at->lte(now())) {
                        $service->deliver($notification);
                    }
                });
            });
        return self::SUCCESS;
    }
}
