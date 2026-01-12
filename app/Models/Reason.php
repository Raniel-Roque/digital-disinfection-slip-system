<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reason extends Model
{
    protected $fillable = [
        'reason_text',
        'is_disabled',
    ];

    protected $casts = [
        'is_disabled' => 'boolean',
    ];

    public function getDisabledAttribute()
    {
        return $this->is_disabled;
    }

    public function setDisabledAttribute($value)
    {
        $this->is_disabled = $value;
    }
}