<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use App\Models\DisinfectionSlip;
use App\Models\Issue;
use App\Models\User;
use App\Models\Vehicle;
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
        // Also clean up their photos
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("Processing DisinfectionSlips...");
        }
        
        $slipsToDelete = DisinfectionSlip::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->get();
        
        $slipsDeleted = $slipsToDelete->count();
        $slipAttachmentsDeleted = 0;
        
        foreach ($slipsToDelete as $slip) {
            // Delete all photos associated with this slip
            if ($slip->photo_ids && is_array($slip->photo_ids)) {
                foreach ($slip->photo_ids as $attachmentId) {
                    $Photo = \App\Models\Photo::find($attachmentId);
                    if ($Photo && $Photo->file_path !== 'images/logo/BGC.png') {
                        // Delete the physical file
                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($Photo->file_path)) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($Photo->file_path);
                        }
                        // Delete the Photo record
                        $Photo->forceDelete();
                        $slipAttachmentsDeleted++;
                    }
                }
            }
            
            // Force delete the slip
            $slip->forceDelete();
        }
        
        $totalDeleted += $slipsDeleted;
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("  - Deleted {$slipsDeleted} disinfection slip(s) and {$slipAttachmentsDeleted} Photo(s)");
        }
        
        // 2. Hard delete soft-deleted Issues
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("Processing Issues...");
        }
        $issuesDeleted = Issue::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->count();
        
        Issue::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->forceDelete();
        
        $totalDeleted += $issuesDeleted;
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("  - Deleted {$issuesDeleted} issue(s)");
        }

        // 3. Hard delete soft-deleted vehicles (will cascade delete related slips via foreign key)
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("Processing Vehicles...");
        }
        $vehiclesDeleted = Vehicle::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->count();
        
        Vehicle::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->forceDelete();
        
        $totalDeleted += $vehiclesDeleted;
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("  - Deleted {$vehiclesDeleted} vehicle(s)");
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
        // Also clean up their logo photos
        if ($this->output && method_exists($this->output, 'writeln')) {
            $this->info("Processing Locations...");
        }
        
        $locationsToDelete = Location::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->get();
        
        $locationsDeleted = $locationsToDelete->count();
        $locationAttachmentsDeleted = 0;
        
        foreach ($locationsToDelete as $location) {
            // Delete the location's logo Photo if it exists
            if ($location->photo_id) {
                $Photo = \App\Models\Photo::find($location->photo_id);
                if ($Photo && $Photo->file_path !== 'images/logo/BGC.png') {
                    // Delete the physical file
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($Photo->file_path)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($Photo->file_path);
                    }
                    // Delete the Photo record
                    $Photo->forceDelete();
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