<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\TableSit\WebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TableSitWebhookController extends Controller
{
    public function __invoke(Request $request, WebhookSignature $signature)
    {
        $secret = (string) config('services.tablesit.webhook_secret');
        abort_if($secret === '', 503, 'Webhook is not configured.');
        $raw = $request->getContent();
        abort_if(strlen($raw) > 1048576, 413);
        abort_unless($signature->valid($raw, (string) $request->header('Tablesit-Signature'), $secret, time()), 401);
        $event = json_decode($raw, true);
        abort_unless(is_array($event), 400);
        $validator = Validator::make($event, [
            'id' => ['required', 'string', 'max:128', 'regex:/^evt_[a-zA-Z0-9_-]+$/'],
            'type' => ['required', 'string', 'max:64'],
            'livemode' => ['required', 'boolean'],
            'organization.uid' => ['required', 'uuid'],
            'data.object.uid' => ['required', 'uuid'],
        ]);
        abort_if($validator->fails(), 422);
        abort_unless(is_bool($event['livemode']), 422);
        $supported = in_array($event['type'], [
            'booking.created', 'booking.updated', 'booking.confirmed', 'booking.seated',
            'booking.completed', 'booking.no_show', 'booking.cancelled', 'booking.rejected', 'booking.deleted',
        ], true);
        $known = Organization::where('external_provider', 'tablesit')
            ->where('external_id', $event['organization']['uid'])->where('is_active', true)->exists();
        $status = ! $event['livemode'] ? 'test' : (! $supported || ! $known ? 'ignored' : 'pending');
        // Only routing metadata is persisted; webhook customer data is never logged or stored.
        DB::table('tablesit_webhook_events')->insertOrIgnore([
            'event_id' => $event['id'], 'event_type' => $event['type'],
            'organization_uid' => $event['organization']['uid'], 'booking_uid' => $event['data']['object']['uid'],
            'livemode' => $event['livemode'], 'status' => $status,
            'available_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        return response()->json(['received' => true], 202);
    }
}
