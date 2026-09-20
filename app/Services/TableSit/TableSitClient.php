<?php

namespace App\Services\TableSit;

use App\Models\Organization;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TableSitClient
{
    public function bookings(
        Organization $organization,
        string $dateFrom,
        string $dateTo,
        int $page = 1,
    ): array {
        $response = $this->request($organization)->get('/bookings', [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'page' => $page,
            'per_page' => 50,
        ]);

        $response->throw();

        return $response->json();
    }

    private function request(Organization $organization): PendingRequest
    {
        $partnerApiKey = config('services.tablesit.partner_api_key');

        if ($partnerApiKey) {
            if (! $organization->external_id) {
                throw new RuntimeException("據點 {$organization->name} 尚未設定 TableSit organizationUid。");
            }

            return $this->baseRequest()
                ->withToken($partnerApiKey)
                ->withHeader('X-Tablesit-Organization', $organization->external_id);
        }

        $apiKeys = config('services.tablesit.api_keys', []);
        $apiKey = $apiKeys[$organization->slug] ?? null;

        if (! $apiKey) {
            throw new RuntimeException("據點 {$organization->name} 尚未設定 TableSit API 憑證。");
        }

        return $this->baseRequest()->withToken($apiKey);
    }

    private function baseRequest(): PendingRequest
    {
        return Http::baseUrl(config('services.tablesit.base_url'))
            ->acceptJson()
            ->timeout(config('services.tablesit.timeout', 15))
            ->retry(3, 500, throw: false);
    }
}
