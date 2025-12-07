<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Log;
use App\Models\Setting;

class CleanLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete logs older than the retention period set in settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get retention period from settings (default to 6 months if not set)
        $retentionSetting = Setting::where('setting_name', 'log_retention_months')->first();
        $retentionMonths = $retentionSetting ? (int) $retentionSetting->value : 6;

        // Calculate the cutoff date
        $cutoffDate = now()->subMonths($retentionMonths);

        $this->info("Cleaning logs older than {$retentionMonths} months (before {$cutoffDate->format('Y-m-d H:i:s')})...");

        // Delete logs older than retention period
        $deletedCount = Log::where('created_at', '<', $cutoffDate)->delete();

        $this->info("Cleanup completed: {$deletedCount} log(s) deleted.");

        return self::SUCCESS;
    }
}

