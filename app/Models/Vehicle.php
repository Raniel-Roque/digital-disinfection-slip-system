<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'vehicles'; // Keep existing table name
    
    protected $fillable = [
        'vehicle',
        'disabled',
    ];

    public function disinfectionSlips()
    {
        return $this->hasMany(DisinfectionSlip::class);
    }
}
