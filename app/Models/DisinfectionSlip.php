<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisinfectionSlip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slip_id',
        'truck_id',
        'location_id',
        'destination_id',
        'driver_id',
        'reason_for_disinfection',
        'attachment_id',
        'hatchery_guard_id',
        'received_guard_id',
        'status',
        'completed_at',
    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($slip) {
            if (empty($slip->slip_id)) {
                $slip->slip_id = self::generateSlipId();
            }
        });
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

    public function attachment()
    {
        return $this->belongsTo(Attachment::class);
    }

    public function hatcheryGuard()
    {
        return $this->belongsTo(User::class, 'hatchery_guard_id');
    }

    public function receivedGuard()
    {
        return $this->belongsTo(User::class, 'received_guard_id');
    }
}