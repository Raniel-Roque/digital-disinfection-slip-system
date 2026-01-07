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

        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("Cleaning soft-deleted records older than {$retentionMonths} months (before {$cutoffDate->format('Y-m-d H:i:s')})...");
        }

        $totalDeleted = 0;

        // 1. Hard delete soft-deleted DisinfectionSlips first (orphaned ones or ones that should be cleaned independently)
        // Also clean up their attachments
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("Processing DisinfectionSlips...");
        }
        
        $slipsToDelete = DisinfectionSlip::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->get();
        
        $slipsDeleted = $slipsToDelete->count();
        $slipAttachmentsDeleted = 0;
        
        foreach ($slipsToDelete as $slip) {
            // Delete all attachments associated with this slip
            if ($slip->attachment_ids && is_array($slip->attachment_ids)) {
                foreach ($slip->attachment_ids as $attachmentId) {
                    $attachment = \App\Models\Attachment::find($attachmentId);
                    if ($attachment && $attachment->file_path !== 'images/logo/BGC.png') {
                        // Delete the physical file
                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path)) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
                        }
                        // Delete the attachment record
                        $attachment->forceDelete();
                        $slipAttachmentsDeleted++;
                    }
                }
            }
            
            // Force delete the slip
            $slip->forceDelete();
        }
        
        $totalDeleted += $slipsDeleted;
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("  - Deleted {$slipsDeleted} disinfection slip(s) and {$slipAttachmentsDeleted} attachment(s)");
        }

        // 2. Hard delete soft-deleted Reports
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("Processing Reports...");
        }
        $reportsDeleted = Report::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->count();
        
        Report::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->forceDelete();
        
        $totalDeleted += $reportsDeleted;
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("  - Deleted {$reportsDeleted} report(s)");
        }

        // 3. Hard delete soft-deleted Trucks (will cascade delete related slips via foreign key)
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("Processing Trucks...");
        }
        $trucksDeleted = Truck::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->count();
        
        Truck::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->forceDelete();
        
        $totalDeleted += $trucksDeleted;
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("  - Deleted {$trucksDeleted} truck(s)");
        }

        // 4. Hard delete soft-deleted Drivers (will cascade delete related slips via foreign key)
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("Processing Drivers...");
        }
        $driversDeleted = Driver::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->count();
        
        Driver::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->forceDelete();
        
        $totalDeleted += $driversDeleted;
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("  - Deleted {$driversDeleted} driver(s)");
        }

        // 5. Hard delete soft-deleted Locations (will cascade delete related slips via foreign key)
        // Also clean up their logo attachments
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("Processing Locations...");
        }
        
        $locationsToDelete = Location::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->get();
        
        $locationsDeleted = $locationsToDelete->count();
        $locationAttachmentsDeleted = 0;
        
        foreach ($locationsToDelete as $location) {
            // Delete the location's logo attachment if it exists
            if ($location->attachment_id) {
                $attachment = \App\Models\Attachment::find($location->attachment_id);
                if ($attachment && $attachment->file_path !== 'images/logo/BGC.png') {
                    // Delete the physical file
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
                    }
                    // Delete the attachment record
                    $attachment->forceDelete();
                    $locationAttachmentsDeleted++;
                }
            }
            
            // Force delete the location
            $location->forceDelete();
        }
        
        $totalDeleted += $locationsDeleted;
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("  - Deleted {$locationsDeleted} location(s) and {$locationAttachmentsDeleted} logo(s)");
        }

        // 6. Hard delete soft-deleted Users (will cascade delete related slips via foreign key)
        // Note: Users are referenced as guards in slips, so cascading will handle related slips
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("Processing Users...");
        }
        $usersDeleted = User::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->count();
        
        User::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->forceDelete();
        
        $totalDeleted += $usersDeleted;
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("  - Deleted {$usersDeleted} user(s)");
        }

        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("Cleanup completed: {$totalDeleted} total record(s) hard deleted.");
        }

        return self::SUCCESS;
    }
}