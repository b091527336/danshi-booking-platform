<?php

namespace App\Services\TableSit;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\SyncRun;
use Illuminate\Support\Facades\DB;
use Throwable;

class BookingSyncService
{
    public function __construct(
        private readonly TableSitClient $client,
        private readonly BookingMapper $mapper,
    ) {
    }

    public function sync(Organization $organization, ?string $updatedAfter = null): SyncRun
    {
        $run = SyncRun::create([
            'organization_id' => $organization->id,
            'status' => 'running',
            'started_at' => now(),
            'meta' => ['updated_after' => $updatedAfter],
        ]);

        try {
            $page = 1;

            do {
                $response = $this->client->bookings($organization, $updatedAfter, $page);
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

    private function syncOne(Organization $organization, array $payload, SyncRun $run): void
    {
        DB::transaction(function () use ($organization, $payload, $run) {
            $mapped = $this->mapper->booking($payload, $organization->timezone);
            $customerData = $this->mapper->customer($payload);
            $customer = $customerData ? $this->resolveCustomer($customerData) : null;

            $booking = Booking::firstOrNew([
                'external_provider' => 'tablesit',
                'external_id' => $mapped['external_id'],
            ]);
            $isNew = ! $booking->exists;

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

            $run->increment($isNew ? 'created_count' : 'updated_count');
        });
    }

    private function resolveCustomer(array $data): Customer
    {
        $customer = null;

        if ($data['email']) {
            $customer = Customer::where('email', $data['email'])->first();
        }

        if (! $customer && $data['phone']) {
            $customer = Customer::where('phone', $data['phone'])->first();
        }

        $customer ??= new Customer();
        $customer->fill(array_filter($data, fn ($value) => $value !== null))->save();

        return $customer;
    }
}
