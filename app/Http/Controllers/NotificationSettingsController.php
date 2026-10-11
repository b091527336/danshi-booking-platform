<?php

namespace App\Http\Controllers;

use App\Models\BookingNotification;
use App\Models\LinePairingCode;
use App\Models\LineRecipient;
use App\Models\Organization;
use App\Services\BookingNotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NotificationSettingsController extends Controller
{
    public function index(Request $request)
    {
        $organizations = Organization::whereIn('id', $request->user()->organizationIds())->orderBy('id')->get();
        $selected = $organizations->firstWhere('id', (int) $request->query('organization_id')) ?? $organizations->first();
        $settings = array_merge(BookingNotificationTemplate::defaults(), $selected?->settings['notifications'] ?? []);
        $recipients = LineRecipient::whereIn('organization_id', $organizations->pluck('id'))->get();
        $logs = BookingNotification::whereHas('booking', fn ($query) => $query->whereIn('organization_id', $organizations->pluck('id')))
            ->with('booking.organization')->latest()->limit(20)->get();
        $customers = \App\Models\Customer::whereHas('bookings', fn ($query) => $query->where('organization_id', $selected?->id ?? 0))->orderBy('name')->get(['id','name']);
        return view('notifications.index', compact('organizations', 'selected', 'settings', 'recipients', 'logs') + [
            'tokens'=>BookingNotificationTemplate::TOKENS, 'customers'=>$customers,
            'channels'=>array_keys(config('services.line.channels', [])),
        ]);
    }

    public function update(Request $request, BookingNotificationTemplate $templates)
    {
        $data = $request->validate([
            'organization_ids'=>['required','array','min:1'],
            'organization_ids.*'=>['required','integer','distinct',Rule::in($request->user()->organizationIds())],
            'channel'=>['nullable','string',Rule::in(array_keys(config('services.line.channels', [])))],
            'staff_enabled'=>['nullable','boolean'], 'customer_enabled'=>['nullable','boolean'],
            'staff_template'=>['required','string','max:1500'], 'customer_template'=>['required','string','max:1500'],
        ]);
        foreach (['staff_template','customer_template'] as $field) {
            $templates->validate($data[$field], $field);
        }
        DB::transaction(function () use ($data, $request) {
            foreach (Organization::whereIn('id', $data['organization_ids'])->lockForUpdate()->get() as $organization) {
                $settings = $organization->settings ?? [];
                $settings['notifications'] = array_merge($settings['notifications'] ?? [], [
                    'staff_enabled'=>$request->boolean('staff_enabled'), 'customer_enabled'=>$request->boolean('customer_enabled'),
                    'staff_template'=>$data['staff_template'], 'customer_template'=>$data['customer_template'],
                ]);
                if ($request->user()->isAdmin() && $request->exists('channel')) {
                    $settings['notifications']['channel'] = $data['channel'] ?? null;
                }
                $organization->update(['settings'=>$settings]);
            }
        });
        return redirect()->route('notifications.index', ['organization_id'=>$data['organization_ids'][0]])
            ->with('success','通知內容已儲存。LINE 綁定完成後才會發送；不會補發過去預約。');
    }

    public function pair(Request $request)
    {
        $data = $request->validate([
            'organization_ids'=>['required','array','min:1'],
            'organization_ids.*'=>['required','integer','distinct',Rule::in($request->user()->organizationIds())],
            'audience'=>['required',Rule::in(['staff','customer'])],
            'customer_id'=>['nullable','integer'],
        ]);
        $organizations = Organization::whereIn('id', $data['organization_ids'])->get();
        $channels = $organizations->map(fn ($org) => $org->settings['notifications']['channel'] ?? null)->unique();
        abort_unless($channels->count() === 1 && $channels->first(), 422, '請先完成這些據點的 LINE 帳號設定。');
        $channel = $channels->first();
        abort_unless(config('services.line.channels.'.$channel.'.secret') && config('services.line.channels.'.$channel.'.token'), 422, 'LINE 連線尚未完成。');
        if ($data['audience'] === 'customer') {
            abort_unless(count($data['organization_ids']) === 1 && ! empty($data['customer_id']) &&
                $organizations->first()->bookings()->where('customer_id', $data['customer_id'])->exists(), 403);
        }
        $code = Str::random(32);
        LinePairingCode::create([
            'code_hash'=>hash('sha256',$code), 'channel'=>$channel, 'audience'=>$data['audience'],
            'organization_ids'=>$data['organization_ids'], 'customer_id'=>$data['audience']==='customer' ? $data['customer_id'] : null,
            'issued_by'=>$request->user()->id, 'expires_at'=>now()->addMinutes(30),
        ]);
        return redirect()->route('notifications.index', ['organization_id'=>$data['organization_ids'][0]])
            ->with('pairing_code','DBP-'.$code)->with('pairing_audience',$data['audience']);
    }
}
