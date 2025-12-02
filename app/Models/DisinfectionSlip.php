<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DisinfectionSlip extends Model
{
    use HasFactory;
    
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
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slip_id)) {
                $year = date('y');
                $prefix = $year . '-';

                $lastSlip = self::where('slip_id', 'LIKE', $prefix . '%')
                    ->orderBy('slip_id', 'desc')
                    ->lockForUpdate()
                    ->first();

                $nextNumber = 1;
                if ($lastSlip) {
                    $lastNumber = (int) substr($lastSlip->slip_id, 3);
                    $nextNumber = $lastNumber + 1;
                }

                $model->slip_id = sprintf("%s-%05d", $year, $nextNumber);
            }
        });
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
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