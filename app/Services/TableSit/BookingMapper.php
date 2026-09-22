<?php

namespace App\Services\TableSit;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use RuntimeException;

class BookingMapper
{
    public function collection(array $response): array
    {
        $items = Arr::get($response, 'data');

        if (! is_array($items) || ! array_is_list($items)) {
            throw new RuntimeException('TableSit 回應中找不到 data 預約陣列。');
        }

        return $items;
    }

    public function booking(array $payload, string $fallbackTimezone): array
    {
        $externalId = Arr::get($payload, 'uid');
        $startsAt = Arr::get($payload, 'start_at');

        if (! $externalId || ! $startsAt) {
            throw new RuntimeException('TableSit 預約缺少必要的 uid 或 start_at。');
        }

        return [
            'external_id' => (string) $externalId,
            'status' => $this->status((string) Arr::get($payload, 'status', 'confirmed')),
            'starts_at' => CarbonImmutable::parse($startsAt)->setTimezone($fallbackTimezone),
            'ends_at' => ($endsAt = Arr::get($payload, 'end_at'))
                ? CarbonImmutable::parse($endsAt)->setTimezone($fallbackTimezone)
                : null,
            'party_size' => is_numeric(Arr::get($payload, 'client_count'))
                ? (int) Arr::get($payload, 'client_count')
                : null,
            'service_name' => $this->serviceNames(Arr::get($payload, 'services', [])),
            'notes' => $this->publicNotes(Arr::get($payload, 'public_notes', [])),
            'source' => Arr::get($payload, 'source'),
        ];
    }

    public function customer(array $payload): ?array
    {
        $customer = Arr::get($payload, 'client');

        if (! is_array($customer)) {
            return null;
        }

        $name = Arr::get($customer, 'name');
        $phone = Arr::get($customer, 'phone');
        $email = Arr::get($customer, 'email');

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
        $totalPages = (int) Arr::get($response, 'meta.total_pages', $currentPage);

        return $currentPage < $totalPages;
    }

    private function serviceNames(array $services): ?string
    {
        $names = Collection::make($services)
            ->pluck('name')
            ->filter()
            ->implode('、');

        return $names !== '' ? $names : null;
    }

    private function publicNotes(array $notes): ?string
    {
        $comments = Collection::make($notes)
            ->map(fn ($note) => is_array($note) ? Arr::get($note, 'comment') : $note)
            ->filter()
            ->implode("\n");

        return $comments !== '' ? $comments : null;
    }

    private function status(string $status): string
    {
        return match (strtolower($status)) {
            'awaiting_payment',
            'waitlist_offered',
            'awaiting_client_reconfirmation',
            'waitlist_queued',
            'requested' => 'pending',
            'confirmed', 'seated' => 'confirmed',
            'completed' => 'completed',
            'cancelled', 'rejected' => 'cancelled',
            'no_show' => 'no_show',
            default => 'pending',
        };
    }
}
