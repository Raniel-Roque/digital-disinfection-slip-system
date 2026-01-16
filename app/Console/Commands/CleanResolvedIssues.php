<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Issue;
use App\Models\Setting;

class CleanResolvedIssues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:resolved-issues';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete resolved issues older than the retention period set in settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get retention period from settings (default to 3 months if not set)
        $retentionSetting = Setting::where('setting_name', 'resolved_issues_retention_months')->first();
        $retentionMonths = $retentionSetting ? (int) $retentionSetting->value : 3;

        // Calculate the cutoff date
        $cutoffDate = now()->subMonths($retentionMonths);

        $this->info("Cleaning resolved issues older than {$retentionMonths} months (before {$cutoffDate->format('Y-m-d H:i:s')})...");

        // Delete resolved issues older than retention period
        $deletedCount = Issue::whereNotNull('resolved_at')
            ->where('resolved_at', '<', $cutoffDate)
            ->delete();

        $this->info("Cleanup completed: {$deletedCount} resolved issue(s) deleted.");

        return self::SUCCESS;
    }
}

