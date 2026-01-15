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
        'attachment_ids',
        'hatchery_guard_id',
        'received_guard_id',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'attachment_ids' => 'array',
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

        // Delete attachments when slip is force deleted
        static::deleting(function ($slip) {
            // Only delete attachments on force delete (hard delete), not soft delete
            if ($slip->isForceDeleting()) {
                $slip->deleteAttachments();
            }
        });

        // Clean up orphaned attachments when attachment_ids is updated
        static::updating(function ($slip) {
            // Only check if attachment_ids is being modified
            if ($slip->isDirty('attachment_ids')) {
                $oldAttachmentIds = $slip->getOriginal('attachment_ids') ?? [];
                $newAttachmentIds = $slip->attachment_ids ?? [];
                
                // Find attachments that were removed (in old but not in new)
                $removedIds = array_diff($oldAttachmentIds, $newAttachmentIds);
                
                if (!empty($removedIds)) {
                    $slip->cleanupOrphanedAttachments($removedIds);
                }
            }
        });
    }

    /**
     * Delete all attachments associated with this slip
     */
    public function deleteAttachments()
    {
        if (!$this->attachment_ids || empty($this->attachment_ids)) {
            return;
        }

        $attachments = Attachment::whereIn('id', $this->attachment_ids)->get();
        
        foreach ($attachments as $attachment) {
            // Delete the file from storage
            if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                // Don't delete default logo (BGC.png)
                if ($attachment->file_path !== 'images/logo/BGC.png') {
                    Storage::disk('public')->delete($attachment->file_path);
                }
            }
            
            // Check if attachment is used by locations (attachment_id)
            $isUsedByLocation = DB::table('locations')
                ->where('attachment_id', $attachment->id)
                ->exists();
            
            // Only delete attachment record if not used by locations
            if (!$isUsedByLocation) {
                $attachment->forceDelete();
            }
        }
    }

    /**
     * Clean up orphaned attachments that are no longer referenced by this slip
     */
    private function cleanupOrphanedAttachments(array $attachmentIds)
    {
        if (empty($attachmentIds)) {
            return;
        }

        $attachments = Attachment::whereIn('id', $attachmentIds)->get();
        
        foreach ($attachments as $attachment) {
            // Check if this attachment is still referenced by any other slip
            $isStillReferenced = DisinfectionSlip::where('id', '!=', $this->id)
                ->whereJsonContains('attachment_ids', $attachment->id)
                ->exists();
            
            // Only delete if not referenced by any other slip and not used by locations
            if (!$isStillReferenced) {
                // Check if attachment is used by locations (attachment_id, not logo_attachment_id based on migration)
                $isUsedByLocation = DB::table('locations')
                    ->where('attachment_id', $attachment->id)
                    ->exists();
                
                if (!$isUsedByLocation) {
                    // Delete the file from storage (except BGC.png)
                    if ($attachment->file_path && $attachment->file_path !== 'images/logo/BGC.png') {
                        if (Storage::disk('public')->exists($attachment->file_path)) {
                            Storage::disk('public')->delete($attachment->file_path);
                        }
                    }
                    
                    // Hard delete the attachment record
                    $attachment->forceDelete();
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
        return $this->belongsTo(Truck::class);
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
     * Get a single attachment (for backward compatibility)
     * Returns the first attachment if multiple exist
     */
    public function attachment()
    {
        if (!$this->attachment_ids || empty($this->attachment_ids)) {
            return null;
        }
        return Attachment::find($this->attachment_ids[0]);
    }

    /**
     * Get all attachments as a collection
     */
    public function attachments()
    {
        if (!$this->attachment_ids || empty($this->attachment_ids)) {
            return collect([]);
        }
        return Attachment::whereIn('id', $this->attachment_ids)->get();
    }

    public function hatcheryGuard()
    {
        return $this->belongsTo(User::class, 'hatchery_guard_id');
    }

    public function receivedGuard()
    {
        return $this->belongsTo(User::class, 'received_guard_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'slip_id');
    }
}