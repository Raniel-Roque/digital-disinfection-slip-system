<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'location_name',
        'photo_id', // updated from logo_attachment_id
        'disabled',
        'create_slip',
    ];

    protected function casts(): array
    {
        return [
            'create_slip' => 'boolean',
            'disabled' => 'boolean',
        ];
    }

    // Logo Photo (images/logos/)
    public function Photo()
    {
        return $this->belongsTo(Photo::class, 'photo_id');
    }

    // Slips originating from this location
    public function disinfectionSlips()
    {
        return $this->hasMany(DisinfectionSlip::class, 'location_id');
    }

    // Slips destined to this location
    public function destinationSlips()
    {
        return $this->hasMany(DisinfectionSlip::class, 'destination_id');
    }
}
