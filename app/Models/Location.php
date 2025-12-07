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
        'attachment_id', // updated from logo_attachment_id
        'disabled',
    ];

    // Logo attachment (images/logos/)
    public function attachment()
    {
        return $this->belongsTo(Attachment::class, 'attachment_id');
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
