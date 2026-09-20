<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\TableSit\BookingSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncTableSitBookings extends Command
{
    protected $signature = 'tablesit:sync-bookings
        {organization? : DBP organization ID or slug}
        {--since= : Only pull records updated after this ISO-8601 timestamp}';

    protected $description = 'Pull TableSit bookings into DBP';

    public function handle(BookingSyncService $service): int
    {
        $organizations = Organization::query()
            ->where('is_active', true)
            ->whereNotNull('external_id')
            ->when($this->argument('organization'), function ($query, $value) {
                $query->where(fn ($query) => $query
                    ->whereKey($value)
                    ->orWhere('slug', $value));
            })
            ->get();

        if ($organizations->isEmpty()) {
            $this->warn('找不到可同步且已綁定 TableSit ID 的據點.');

            return self::FAILURE;
        }

        $failed = false;

        foreach ($organizations as $organization) {
            $this->line("同步：{$organization->name}");

            try {
                $run = $service->sync($organization, $this->option('since'));
                $this->info("完成：新增 {$run->created_count}、更新 {$run->updated_count}、失敗 {$run->failed_count}");
            } catch (Throwable $exception) {
                $failed = true;
                $this->error("失敗：{$exception->getMessage()}");
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
