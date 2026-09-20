<?php

use App\Models\Organization;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('dbp:status', function () {
    $this->info('Danshi Booking Platform is ready.');
})->purpose('Check the DBP application status');

Artisan::command('tablesit:create-test-organizations {--count=6}', function () {
    $apiKey = config('services.tablesit.partner_api_key');
    $ownerEmail = config('services.tablesit.owner_email');
    $partnerBaseUrl = config('services.tablesit.partner_base_url');
    $count = max(1, min(6, (int) $this->option('count')));

    if (! $apiKey) {
        $this->error('TABLESIT_PARTNER_API_KEY 尚未設定.');

        return self::FAILURE;
    }

    if (! $ownerEmail) {
        $this->error('TABLESIT_OWNER_EMAIL 或 DBP_ADMIN_EMAIL 尚未設定.');

        return self::FAILURE;
    }

    for ($number = 1; $number <= $count; $number++) {
        $slug = "anasa-staging-{$number}";

        if (Organization::where('slug', $slug)->exists()) {
            $this->line("略過已存在據點：{$slug}");
            continue;
        }

        $name = "ANASA 測試據點 {$number}";
        $this->line("正在建立：{$name}");

        $response = Http::baseUrl($partnerBaseUrl)
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(config('services.tablesit.timeout', 15))
            ->post('/orgs', [
                'name' => $name,
                'ownerEmail' => $ownerEmail,
                'ownerDisplayName' => 'ANASA',
                'tier' => 'pro',
                'locale' => 'zh-TW',
                'timezone' => 'Asia/Taipei',
                'currency' => 'TWD',
            ]);

        if (! $response->successful()) {
            $this->error("建立失敗（HTTP {$response->status()}）：".$response->body());
            $this->warn('為避免重複建立，程序已停止且不會自動重試。');

            return self::FAILURE;
        }

        $data = $response->json();
        $organizationUid = $data['organizationUid'] ?? null;

        if (! $organizationUid) {
            $this->error('TableSit 回應缺少 organizationUid，程序已停止且不會自動重試。');

            return self::FAILURE;
        }

        Organization::create([
            'name' => $name,
            'slug' => $slug,
            'external_provider' => 'tablesit',
            'external_id' => $organizationUid,
            'timezone' => 'Asia/Taipei',
            'is_active' => true,
            'settings' => [
                'tablesit_slug' => $data['slug'] ?? null,
                'booking_url' => $data['bookingUrl'] ?? null,
                'tier' => $data['tier'] ?? 'pro',
                'environment' => 'staging',
            ],
        ]);

        $this->info("建立完成：{$name}（{$organizationUid}）");
    }

    $this->info('測試據點建立程序完成。');

    return self::SUCCESS;
})->purpose('Create ANASA staging organizations through the TableSit Partner API');

Schedule::command('tablesit:sync-bookings')
    ->everyFifteenMinutes()
    ->withoutOverlapping(20);
