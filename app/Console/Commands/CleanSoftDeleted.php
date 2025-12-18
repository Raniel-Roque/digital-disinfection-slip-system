<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use App\Models\DisinfectionSlip;
use App\Models\Report;
use App\Models\User;
use App\Models\Truck;
use App\Models\Location;
use App\Models\Driver;
use App\Models\Attachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleanSoftDeleted extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:soft-deleted';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hard delete soft-deleted records older than the retention period set in settings (cascades to disinfection slips)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get retention period from settings (default to 3 months if not set)
        $retentionSetting = Setting::where('setting_name', 'soft_deleted_retention_months')->first();
        $retentionMonths = $retentionSetting ? (int) $retentionSetting->value : 3;

        // Calculate the cutoff date
        $cutoffDate = now()->subMonths($retentionMonths);

        $this->info("Cleaning soft-deleted records older than {$retentionMonths} months (before {$cutoffDate->format('Y-m-d H:i:s')})...");

        $totalDeleted = 0;

        // 1. Hard delete soft-deleted DisinfectionSlips first (orphaned ones or ones that should be cleaned independently)
        $this->info("Processing DisinfectionSlips...");
        $slipsDeleted = DisinfectionSlip::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->count();
        
        DisinfectionSlip::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->forceDelete();
        
        $totalDeleted += $slipsDeleted;
        $this->info("  - Deleted {$slipsDeleted} disinfection slip(s)");

        // 2. Hard delete soft-deleted Reports
        $this->info("Processing Reports...");
        $reportsDeleted = Report::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->count();
        
        Report::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->forceDelete();
        
        $totalDeleted += $reportsDeleted;
        $this->info("  - Deleted {$reportsDeleted} report(s)");

        // 3. Hard delete soft-deleted Trucks (will cascade delete related slips via foreign key)
        $this->info("Processing Trucks...");
        $trucksDeleted = Truck::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->count();
        
        Truck::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->forceDelete();
        
        $totalDeleted += $trucksDeleted;
        $this->info("  - Deleted {$trucksDeleted} truck(s)");

        // 4. Hard delete soft-deleted Drivers (will cascade delete related slips via foreign key)
        $this->info("Processing Drivers...");
        $driversDeleted = Driver::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->count();
        
        Driver::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->forceDelete();
        
        $totalDeleted += $driversDeleted;
        $this->info("  - Deleted {$driversDeleted} driver(s)");

        // 5. Hard delete soft-deleted Locations (will cascade delete related slips via foreign key)
        $this->info("Processing Locations...");
        $locationsDeleted = Location::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->count();
        
        Location::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->forceDelete();
        
        $totalDeleted += $locationsDeleted;
        $this->info("  - Deleted {$locationsDeleted} location(s)");

        // 6. Hard delete soft-deleted Users (will cascade delete related slips via foreign key)
        // Note: Users are referenced as guards in slips, so cascading will handle related slips
        $this->info("Processing Users...");
        $usersDeleted = User::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->count();
        
        User::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->forceDelete();
        
        $totalDeleted += $usersDeleted;
        $this->info("  - Deleted {$usersDeleted} user(s)");

        // 7. Clean up orphaned attachments (attachments not referenced by any slip or location)
        $this->info("Processing orphaned attachments...");
        $allSlipAttachmentIds = DB::table('disinfection_slips')
            ->whereNotNull('attachment_ids')
            ->get()
            ->flatMap(function ($slip) {
                $ids = json_decode($slip->attachment_ids, true);
                return is_array($ids) ? $ids : [];
            })
            ->unique()
            ->toArray();

        $locationAttachmentIds = DB::table('locations')
            ->whereNotNull('logo_attachment_id')
            ->pluck('logo_attachment_id')
            ->toArray();

        $usedAttachmentIds = array_unique(array_merge($allSlipAttachmentIds, $locationAttachmentIds));

        $orphanedAttachments = Attachment::whereNotIn('id', $usedAttachmentIds)->get();
        $orphanedCount = 0;

        foreach ($orphanedAttachments as $attachment) {
            // Don't delete default logo (BGC.png)
            if ($attachment->file_path === 'images/logo/BGC.png') {
                continue;
            }

            // Delete the file from storage
            if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            // Hard delete the attachment record
            $attachment->forceDelete();
            $orphanedCount++;
        }

        $totalDeleted += $orphanedCount;
        $this->info("  - Deleted {$orphanedCount} orphaned attachment(s)");

        $this->info("Cleanup completed: {$totalDeleted} total record(s) hard deleted.");

        return self::SUCCESS;
    }
}
