<?php

namespace App\Services\TableSit;

use App\Models\Organization;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TableSitClient
{
    public function bookings(Organization $organization, ?string $updatedAfter = null, int $page = 1): array
    {
        if (! $organization->external_id) {
            throw new RuntimeException("據點 {$organization->name} 尚未設定 TableSit Organization ID。");
        }

        $response = $this->request()->get(config('services.tablesit.bookings_path'), array_filter([
            config('services.tablesit.organization_parameter', 'organization_id') => $organization->external_id,
            config('services.tablesit.updated_after_parameter', 'updated_after') => $updatedAfter,
            config('services.tablesit.page_parameter', 'page') => $page,
        ], fn ($value) => $value !== null && $value !== ''));

        $response->throw();

        return $response->json();
    }

    private function request(): PendingRequest
    {
        $baseUrl = config('services.tablesit.base_url');
        $apiKey = config('services.tablesit.api_key');

        if (! $baseUrl || ! $apiKey) {
            throw new RuntimeException('TableSit API 尚未完成設定。');
        }

        $request = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout(config('services.tablesit.timeout', 15))
            ->retry(3, 500, throw: false);

        return match (config('services.tablesit.auth_type', 'bearer')) {
            'header' => $request->withHeader(
                config('services.tablesit.auth_header', 'X-API-Key'),
                $apiKey,
            ),
            default => $request->withToken($apiKey),
        };
    }
}
