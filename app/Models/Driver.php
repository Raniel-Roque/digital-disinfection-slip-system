<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, SoftDeletes; 
    
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'disabled',
    ];

    public function disinfectionSlips()
    {
        return $this->hasMany(DisinfectionSlip::class);
    }

    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }
}
