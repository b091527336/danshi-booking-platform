<?php

namespace App\Services\TableSit;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\SyncRun;
use App\Services\BookingNotificationService;
use Illuminate\Support\Facades\DB;
use Throwable;

class BookingSyncService
{
    public function __construct(
        private readonly TableSitClient $client,
        private readonly BookingMapper $mapper,
    ) {
    }

    public function sync(
        Organization $organization,
        string $dateFrom,
        string $dateTo,
        ?string $updatedSince = null,
    ): SyncRun
    {
        $run = SyncRun::create([
            'organization_id' => $organization->id,
            'status' => 'running',
            'started_at' => now(),
            'meta' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'updated_since' => $updatedSince,
            ],
        ]);

        try {
            $page = 1;

            do {
                $response = $this->client->bookings(
                    $organization,
                    $dateFrom,
                    $dateTo,
                    $page,
                    $updatedSince,
                );
                $items = $this->mapper->collection($response);
                $run->increment('received_count', count($items));

                foreach ($items as $payload) {
                    try {
                        $this->syncOne($organization, $payload, $run);
                    } catch (Throwable $exception) {
                        $run->increment('failed_count');
                        report($exception);
                    }
                }

                $hasNextPage = $this->mapper->hasNextPage($response, $page);
                $page++;
            } while ($hasNextPage && $page <= config('services.tablesit.max_pages', 100));

            $run->refresh()->update([
                'status' => $run->failed_count > 0 ? 'partial' : 'completed',
                'finished_at' => now(),
                'meta' => array_merge($run->meta ?? [], ['pages' => $page - 1]),
            ]);
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 4000),
                'finished_at' => now(),
            ]);

            throw $exception;
        }

        return $run->fresh();
    }

    public function syncOne(Organization $organization, array $payload, ?SyncRun $run = null): void
    {
        DB::transaction(function () use ($organization, $payload, $run) {
            // Serialize webhook and polling writes for this organization.
            Organization::whereKey($organization->id)->lockForUpdate()->firstOrFail();
            $mapped = $this->mapper->booking($payload, $organization->timezone);
            $existing = Booking::where('external_provider', 'tablesit')->where('external_id', $mapped['external_id'])->first();
            if ($existing && $existing->organization_id !== $organization->id) {
                throw new \RuntimeException('Tablesit booking organization mismatch.');
            }
            $incomingVersion = $payload['updated_at'] ?? null;
            $storedVersion = $existing?->provider_payload['updated_at'] ?? null;
            if (($existing?->provider_payload['_dbp_deleted'] ?? false) && (! $incomingVersion || \Carbon\CarbonImmutable::parse($incomingVersion)->lte(\Carbon\CarbonImmutable::parse($storedVersion)))) {
                return;
            }
            if ($incomingVersion && $storedVersion && \Carbon\CarbonImmutable::parse($incomingVersion)->lt(\Carbon\CarbonImmutable::parse($storedVersion))) {
                return;
            }
            $customerData = $this->mapper->customer($payload);
            $customer = $customerData ? $this->resolveCustomer($customerData) : null;

            $booking = Booking::firstOrNew([
                'external_provider' => 'tablesit',
                'external_id' => $mapped['external_id'],
            ]);
            $isNew = ! $booking->exists;

            $previous = $booking->only(['status', 'starts_at', 'ends_at', 'service_name']);
            $booking->fill([
                'organization_id' => $organization->id,
                'customer_id' => $customer?->id,
                'status' => $mapped['status'],
                'source' => $mapped['source'],
                'starts_at' => $mapped['starts_at'],
                'ends_at' => $mapped['ends_at'],
                'party_size' => $mapped['party_size'],
                'service_name' => $mapped['service_name'],
                'notes' => $mapped['notes'],
                'provider_payload' => $payload,
                'synced_at' => now(),
            ])->save();

            if ($isNew || $booking->wasChanged(array_keys($previous))) {
                app(BookingNotificationService::class)->enqueue($booking, $isNew ? '新預約已收到' : ($booking->status === 'cancelled' ? '預約已取消' : '預約已更新'));
            }

            $run?->increment($isNew ? 'created_count' : 'updated_count');
        });
    }

    private function resolveCustomer(array $data): Customer
    {
        $customer = $data['email'] ? Customer::where('email', $data['email'])->first() : null;

        if (! $customer && $data['phone']) {
            $customer = Customer::where('phone', $data['phone'])->first();
        }

        $customer ??= new Customer();
        $customer->fill(array_filter($data, fn ($value) => $value !== null))->save();

        return $customer;
    }
}
