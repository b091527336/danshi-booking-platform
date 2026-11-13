<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\SyncRun;
use App\Services\TableSit\BookingSyncService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class SyncCenterController extends Controller
{
    public function index(Request $request): View
    {
        $configuredSlugs = array_keys(config('services.tablesit.api_keys', []));
        $partnerApiKeyConfigured = filled(config('services.tablesit.partner_api_key'));

        return view('sync.index', [
            'webhookConfigured' => filled(config('services.tablesit.webhook_secret')),
            'webhookEvents' => \Illuminate\Support\Facades\DB::table('tablesit_webhook_events')
                ->whereIn('organization_uid', Organization::whereIn('id', $request->user()->organizationIds())->pluck('external_id'))
                ->latest('id')->limit(20)->get(),
            'runs' => SyncRun::query()
                ->with('organization')
                ->whereIn('organization_id', $request->user()->organizationIds())
                ->latest('started_at')
                ->paginate(20),
            'organizations' => Organization::query()
                ->whereIn('id', $request->user()->organizationIds())
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function (Organization $organization) use ($configuredSlugs, $partnerApiKeyConfigured) {
                    $organization->setAttribute(
                        'api_key_configured',
                        $partnerApiKeyConfigured
                            ? filled($organization->external_id)
                            : in_array($organization->slug, $configuredSlugs, true),
                    );

                    return $organization;
                }),
        ]);
    }

    public function store(
        Request $request,
        Organization $organization,
        BookingSyncService $service,
    ): RedirectResponse {
        abort_unless(in_array($organization->id, $request->user()->organizationIds()), 403);
        abort_unless($organization->is_active, 422, '停用的據點無法同步。');

        $data = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $dateFrom = CarbonImmutable::parse(
            $data['date_from'] ?? now()->subDays(30)->toDateString(),
            config('app.timezone'),
        )->startOfDay()->utc()->toIso8601String();

        $dateTo = CarbonImmutable::parse(
            $data['date_to'] ?? now()->addYear()->toDateString(),
            config('app.timezone'),
        )->endOfDay()->utc()->toIso8601String();

        try {
            $run = $service->sync($organization, $dateFrom, $dateTo);

            return back()->with(
                $run->status === 'completed' ? 'success' : 'warning',
                "{$organization->name} 同步完成：新增 {$run->created_count}、更新 {$run->updated_count}、失敗 {$run->failed_count}。",
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', "{$organization->name} 同步失敗：{$exception->getMessage()}");
        }
    }
}
