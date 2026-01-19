<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Photo;
use App\Models\Setting;
use App\Models\DisinfectionSlip;
use App\Models\Location;
use Illuminate\Support\Facades\Storage;

class CleanPhotos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:photos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete photos older than the retention period set in settings';

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

        $this->info("Cleaning photos older than {$retentionDays} days (before {$cutoffDate->format('Y-m-d H:i:s')})...");

        // Find photos older than retention period
        $oldAttachments = Photo::where('created_at', '<', $cutoffDate)->get();

        $deletedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($oldAttachments as $Photo) {
            // Check if Photo is a logo (used by Location) - these should be preserved
            $isLogo = Location::where('photo_id', $Photo->id)->exists();
            
            // Check if Photo is the default BGC.png logo - this should be preserved
            $isBgcLogo = $Photo->file_path === 'images/logo/BGC.png';

            if ($isLogo || $isBgcLogo) {
                $skippedCount++;
                continue;
            }

            try {
                // First, remove the Photo reference from any disinfection slips
                // Handle both old photo_id (for migration compatibility) and new photo_ids array
                $slipsWithAttachment = DisinfectionSlip::whereJsonContains('photo_ids', $Photo->id)->get();
                
                foreach ($slipsWithAttachment as $slip) {
                    $attachmentIds = $slip->photo_ids ?? [];
                    $attachmentIds = array_filter($attachmentIds, fn($id) => $id != $Photo->id);
                    $slip->update(['photo_ids' => empty($attachmentIds) ? null : array_values($attachmentIds)]);
                }

                // Delete the physical file from storage
                if (Storage::disk('public')->exists($Photo->file_path)) {
                    Storage::disk('public')->delete($Photo->file_path);
                }

                // Hard delete the Photo record
                $Photo->forceDelete();

                $deletedCount++;
            } catch (\Exception $e) {
                $this->error("Error deleting Photo ID {$Photo->id}: {$e->getMessage()}");
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

