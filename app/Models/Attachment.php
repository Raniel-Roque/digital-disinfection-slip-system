<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attachment extends Model
{
    use HasFactory;
    protected $fillable = [
        'file_path',
        'user_id',
    ];

    // If you want: attachments can be reused in future
    public function disinfectionSlips()
    {
        return $this->hasMany(DisinfectionSlip::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class, 'logo_attachment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
