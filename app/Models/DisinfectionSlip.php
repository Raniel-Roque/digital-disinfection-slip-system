<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DisinfectionSlip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slip_id',
        'truck_id',
        'location_id',
        'destination_id',
        'driver_id',
        'reason_id',
        'remarks_for_disinfection',
        'photo_ids',
        'hatchery_guard_id',
        'received_guard_id',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'photo_ids' => 'array',
        ];
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($slip) {
            if (empty($slip->slip_id)) {
                $slip->slip_id = self::generateSlipId();
            }
        });

        // Delete photos when slip is force deleted
        static::deleting(function ($slip) {
            // Only delete photos on force delete (hard delete), not soft delete
            if ($slip->isForceDeleting()) {
                $slip->deleteAttachments();
            }
        });

        // Clean up orphaned photos when photo_ids is updated
        static::updating(function ($slip) {
            // Only check if photo_ids is being modified
            if ($slip->isDirty('photo_ids')) {
                $oldAttachmentIds = $slip->getOriginal('photo_ids') ?? [];
                $newAttachmentIds = $slip->photo_ids ?? [];
                
                // Find photos that were removed (in old but not in new)
                $removedIds = array_diff($oldAttachmentIds, $newAttachmentIds);
                
                if (!empty($removedIds)) {
                    $slip->cleanupOrphanedAttachments($removedIds);
                }
            }
        });
    }

    /**
     * Delete all photos associated with this slip
     */
    public function deleteAttachments()
    {
        if (!$this->photo_ids || empty($this->photo_ids)) {
            return;
        }

        $photos = Photo::whereIn('id', $this->photo_ids)->get();
        
        foreach ($photos as $Photo) {
            // Delete the file from storage
            if ($Photo->file_path && Storage::disk('public')->exists($Photo->file_path)) {
                // Don't delete default logo (BGC.png)
                if ($Photo->file_path !== 'images/logo/BGC.png') {
                    Storage::disk('public')->delete($Photo->file_path);
                }
            }
            
            // Check if Photo is used by locations (photo_id)
            $isUsedByLocation = DB::table('locations')
                ->where('photo_id', $Photo->id)
                ->exists();
            
            // Only delete Photo record if not used by locations
            if (!$isUsedByLocation) {
                $Photo->forceDelete();
            }
        }
    }

    /**
     * Clean up orphaned photos that are no longer referenced by this slip
     */
    private function cleanupOrphanedAttachments(array $attachmentIds)
    {
        if (empty($attachmentIds)) {
            return;
        }

        $photos = Photo::whereIn('id', $attachmentIds)->get();
        
        foreach ($photos as $Photo) {
            // Check if this Photo is still referenced by any other slip
            $isStillReferenced = DisinfectionSlip::where('id', '!=', $this->id)
                ->whereJsonContains('photo_ids', $Photo->id)
                ->exists();
            
            // Only delete if not referenced by any other slip and not used by locations
            if (!$isStillReferenced) {
                // Check if Photo is used by locations (photo_id, not logo_attachment_id based on migration)
                $isUsedByLocation = DB::table('locations')
                    ->where('photo_id', $Photo->id)
                    ->exists();
                
                if (!$isUsedByLocation) {
                    // Delete the file from storage (except BGC.png)
                    if ($Photo->file_path && $Photo->file_path !== 'images/logo/BGC.png') {
                        if (Storage::disk('public')->exists($Photo->file_path)) {
                            Storage::disk('public')->delete($Photo->file_path);
                        }
                    }
                    
                    // Hard delete the Photo record
                    $Photo->forceDelete();
                }
            }
        }
    }

    public static function generateSlipId()
    {
        $year = date('y'); // Get 2-digit year (e.g., 25 for 2025)
        
        // Get the last slip ID for this year
        $lastSlip = self::withTrashed()
            ->where('slip_id', 'like', $year . '-%')
            ->orderBy('slip_id', 'desc')
            ->first();
        
        if ($lastSlip) {
            // Extract the number part and increment
            $lastNumber = (int) substr($lastSlip->slip_id, 3); // Get number after "YY-"
            $newNumber = $lastNumber + 1;
        } else {
            // First slip of the year
            $newNumber = 1;
        }
        
        // Format: YY-00001
        return $year . '-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function truck()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function destination()
    {
        return $this->belongsTo(Location::class, 'destination_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function reason()
    {
        return $this->belongsTo(Reason::class);
    }

    /**
     * Get a single Photo (for backward compatibility)
     * Returns the first Photo if multiple exist
     */
    public function Photo()
    {
        if (!$this->photo_ids || empty($this->photo_ids)) {
            return null;
        }
        return Photo::find($this->photo_ids[0]);
    }

    /**
     * Get all photos as a collection
     */
    public function photos()
    {
        if (!$this->photo_ids || empty($this->photo_ids)) {
            return collect([]);
        }
        return Photo::whereIn('id', $this->photo_ids)->get();
    }

    public function hatcheryGuard()
    {
        return $this->belongsTo(User::class, 'hatchery_guard_id');
    }

    public function receivedGuard()
    {
        return $this->belongsTo(User::class, 'received_guard_id');
    }

    public function issues()
    {
        return $this->hasMany(Issue::class, 'slip_id');
    }
}