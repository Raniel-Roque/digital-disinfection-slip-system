<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;
use App\Models\Setting;

class CleanResolvedReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:resolved-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete resolved reports older than the retention period set in settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get retention period from settings (default to 6 months if not set)
        $retentionSetting = Setting::where('setting_name', 'resolved_reports_retention_months')->first();
        $retentionMonths = $retentionSetting ? (int) $retentionSetting->value : 6;

        // Calculate the cutoff date
        $cutoffDate = now()->subMonths($retentionMonths);

        $this->info("Cleaning resolved reports older than {$retentionMonths} months (before {$cutoffDate->format('Y-m-d H:i:s')})...");

        // Delete resolved reports older than retention period
        $deletedCount = Report::whereNotNull('resolved_at')
            ->where('resolved_at', '<', $cutoffDate)
            ->delete();

        $this->info("Cleanup completed: {$deletedCount} resolved report(s) deleted.");

        return self::SUCCESS;
    }
}

