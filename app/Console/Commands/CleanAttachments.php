<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attachment;
use App\Models\Setting;
use App\Models\DisinfectionSlip;
use App\Models\Location;
use Illuminate\Support\Facades\Storage;

class CleanAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:attachments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete attachments older than the retention period set in settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get retention period from settings (default to 30 days if not set)
        $retentionSetting = Setting::where('setting_name', 'attachment_retention_days')->first();
        $retentionDays = $retentionSetting ? (int) $retentionSetting->value : 30;

        // Calculate the cutoff date
        $cutoffDate = now()->subDays($retentionDays);

        $this->info("Cleaning attachments older than {$retentionDays} days (before {$cutoffDate->format('Y-m-d H:i:s')})...");

        // Find attachments older than retention period
        $oldAttachments = Attachment::where('created_at', '<', $cutoffDate)->get();

        $deletedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($oldAttachments as $attachment) {
            // Check if attachment is a logo (used by Location) - these should be preserved
            $isLogo = Location::where('attachment_id', $attachment->id)->exists();

            if ($isLogo) {
                $skippedCount++;
                continue;
            }

            try {
                // First, remove the attachment reference from any disinfection slips
                // This ensures data consistency before deletion (even though nullOnDelete() handles it)
                DisinfectionSlip::where('attachment_id', $attachment->id)
                    ->update(['attachment_id' => null]);

                // Delete the physical file from storage
                if (Storage::disk('public')->exists($attachment->file_path)) {
                    Storage::disk('public')->delete($attachment->file_path);
                }

                // Hard delete the attachment record
                $attachment->forceDelete();

                $deletedCount++;
            } catch (\Exception $e) {
                $this->error("Error deleting attachment ID {$attachment->id}: {$e->getMessage()}");
                $errorCount++;
            }
        }

        $this->info("Cleanup completed:");
        $this->info("  - Deleted: {$deletedCount}");
        $this->info("  - Skipped (logos): {$skippedCount}");
        if ($errorCount > 0) {
            $this->warn("  - Errors: {$errorCount}");
        }

        return self::SUCCESS;
    }
}

