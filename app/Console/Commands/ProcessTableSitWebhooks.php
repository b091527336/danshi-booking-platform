<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Organization;
use App\Services\TableSit\BookingSyncService;
use App\Services\TableSit\TableSitClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessTableSitWebhooks extends Command
{
    protected $signature = 'tablesit:process-webhooks';
    protected $description = 'Reconcile durable Tablesit webhook events against the latest API state';

    public function handle(TableSitClient $client, BookingSyncService $sync): int
    {
        $events = DB::table('tablesit_webhook_events')->where('status', 'pending')
            ->where('available_at', '<=', now())->orderBy('id')->limit(50)->get();
        foreach ($events as $event) {
            try {
                DB::transaction(function () use ($event, $client, $sync) {
                    $organization = Organization::where('external_provider', 'tablesit')
                        ->where('external_id', $event->organization_uid)->lockForUpdate()->first();
                    $current = DB::table('tablesit_webhook_events')->where('id', $event->id)->lockForUpdate()->first();
                    if ($current->status !== 'pending' || $current->available_at > now()->toDateTimeString()) {
                        return;
                    }
                    $status = 'ignored';
                    if ($organization?->is_active) {
                        // Fetch current state, never apply an out-of-order event snapshot.
                        $payload = $client->booking($organization, $event->booking_uid);
                        if ($payload) {
                            $sync->syncOne($organization, $payload);
                        } elseif ($event->event_type === 'booking.deleted') {
                            // Preserve local audit history rather than permanently deleting bookings.
                            Booking::where('organization_id', $organization->id)
                                ->where('external_provider', 'tablesit')->where('external_id', $event->booking_uid)
                                ->update(['status' => 'cancelled', 'synced_at' => now(), 'provider_payload' => json_encode(['uid' => $event->booking_uid, 'updated_at' => now()->utc()->toIso8601String(), '_dbp_deleted' => true])]);
                        } else {
                            throw new \RuntimeException('Booking unavailable; retry later.');
                        }
                        $status = 'processed';
                    }
                    DB::table('tablesit_webhook_events')->where('id', $event->id)->update([
                        'status' => $status, 'processed_at' => now(), 'error_code' => null, 'updated_at' => now(),
                    ]);
                });
            } catch (Throwable $exception) {
                // Do not log provider responses: they can include customer data or credentials.
                $attempts = $event->attempts + 1;
                DB::table('tablesit_webhook_events')->where('id', $event->id)->where('status', 'pending')->update([
                    'attempts' => $attempts, 'error_code' => 'RECONCILE_FAILED',
                    'status' => $attempts >= 12 ? 'failed' : 'pending',
                    'available_at' => now()->addMinutes(min(60, 2 ** min($attempts, 6))), 'updated_at' => now(),
                ]);
            }
        }
        return self::SUCCESS;
    }
}
