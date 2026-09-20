<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\TableSit\BookingSyncService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class SyncTableSitBookings extends Command
{
    protected $signature = 'tablesit:sync-bookings
        {organization? : DBP organization ID or slug}
        {--date-from= : Inclusive booking start date/time in ISO-8601}
        {--date-to= : Inclusive booking end date/time in ISO-8601}';

    protected $description = 'Pull TableSit bookings into DBP';

    public function handle(BookingSyncService $service): int
    {
        $dateFrom = CarbonImmutable::parse($this->option('date-from') ?: now()->subDays(30))->utc()->toIso8601String();
        $dateTo = CarbonImmutable::parse($this->option('date-to') ?: now()->addYear())->utc()->toIso8601String();

        $organizations = Organization::query()
            ->where('is_active', true)
            ->when($this->argument('organization'), function ($query, $value) {
                $query->where(fn ($query) => $query->whereKey($value)->orWhere('slug', $value));
            })
            ->get();

        if ($organizations->isEmpty()) {
            $this->warn('找不到可同步的啟用據點。');

            return self::FAILURE;
        }

        $failed = false;

        foreach ($organizations as $organization) {
            $this->line("同步：{$organization->name}");

            try {
                $run = $service->sync($organization, $dateFrom, $dateTo);
                $this->info("完成：新增 {$run->created_count}、更新 {$run->updated_count}、失敗 {$run->failed_count}");
            } catch (Throwable $exception) {
                $failed = true;
                $this->error("失敗：{$exception->getMessage()}");
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
