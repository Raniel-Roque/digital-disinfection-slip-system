<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Photo extends Model
{
    use HasFactory;

    protected $table = 'photos'; // Match migration table name

    public $timestamps = true; // Ensure timestamps are enabled

    protected $fillable = [
        'file_path',
        'user_id',
    ];

    // If you want: photos can be reused in future
    public function disinfectionSlips()
    {
        return $this->hasMany(DisinfectionSlip::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class, 'photo_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
