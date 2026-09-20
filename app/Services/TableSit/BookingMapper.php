<?php

namespace App\Services\TableSit;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use RuntimeException;

class BookingMapper
{
    public function collection(array $response): array
    {
        if (array_is_list($response)) {
            return $response;
        }

        foreach (['data', 'bookings', 'results', 'items'] as $key) {
            $items = Arr::get($response, $key);

            if (is_array($items) && array_is_list($items)) {
                return $items;
            }
        }

        throw new RuntimeException('TableSit 回應中找不到預約資料陣列。');
    }

    public function booking(array $payload, string $fallbackTimezone): array
    {
        $externalId = $this->first($payload, ['id', 'booking_id', 'bookingId', 'uuid']);
        $startsAt = $this->first($payload, ['starts_at', 'start_at', 'start', 'startTime', 'datetime']);

        if (! $externalId || ! $startsAt) {
            throw new RuntimeException('預約缺少必要的 id 或開始時間。');
        }

        $timezone = (string) ($this->first($payload, ['timezone', 'time_zone']) ?: $fallbackTimezone);

        return [
            'external_id' => (string) $externalId,
            'status' => $this->status((string) ($this->first($payload, ['status', 'state']) ?: 'confirmed')),
            'starts_at' => CarbonImmutable::parse($startsAt, $timezone)->utc(),
            'ends_at' => ($endsAt = $this->first($payload, ['ends_at', 'end_at', 'end', 'endTime']))
                ? CarbonImmutable::parse($endsAt, $timezone)->utc()
                : null,
            'party_size' => $this->nullableInt($this->first($payload, ['party_size', 'partySize', 'guests', 'covers'])),
            'service_name' => $this->first($payload, ['service.name', 'service_name', 'serviceName']),
            'notes' => $this->first($payload, ['notes', 'note', 'customer_note']),
            'source' => $this->first($payload, ['source', 'channel']),
        ];
    }

    public function customer(array $payload): ?array
    {
        $customer = Arr::get($payload, 'customer', []);
        $source = is_array($customer) ? $customer : [];

        $name = $this->first($source, ['name', 'full_name', 'fullName'])
            ?: $this->first($payload, ['customer_name', 'customerName']);
        $phone = $this->first($source, ['phone', 'phone_number', 'phoneNumber'])
            ?: $this->first($payload, ['customer_phone', 'customerPhone']);
        $email = $this->first($source, ['email'])
            ?: $this->first($payload, ['customer_email', 'customerEmail']);

        if (! $name && ! $phone && ! $email) {
            return null;
        }

        return [
            'name' => (string) ($name ?: '未提供姓名'),
            'phone' => $phone ? (string) $phone : null,
            'email' => $email ? strtolower((string) $email) : null,
        ];
    }

    public function hasNextPage(array $response, int $currentPage): bool
    {
        $lastPage = $this->first($response, ['meta.last_page', 'pagination.last_page', 'last_page']);

        if ($lastPage !== null) {
            return $currentPage < (int) $lastPage;
        }

        return (bool) $this->first($response, ['links.next', 'next', 'next_page']);
    }

    private function first(array $data, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = Arr::get($data, $path);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function status(string $status): string
    {
        return match (strtolower($status)) {
            'pending', 'requested' => 'pending',
            'confirmed', 'accepted', 'booked' => 'confirmed',
            'completed', 'finished' => 'completed',
            'cancelled', 'canceled', 'declined' => 'cancelled',
            'no_show', 'no-show', 'noshow' => 'no_show',
            default => 'confirmed',
        };
    }
}
