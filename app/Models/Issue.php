<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Logger;

class Issue extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'issues'; // Keep existing table name
    
    protected $fillable = [
        'user_id',
        'slip_id',
        'description',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($issue) {
            $slipId = $issue->slip ? $issue->slip->slip_id : null;
            $issueType = $slipId ? "for slip {$slipId}" : "miscellaneous";
            $newValues = $issue->only(['user_id', 'slip_id', 'description']);
            Logger::create(
                self::class,
                $issue->id,
                "Created issue {$issueType}",
                $newValues
            );
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function slip()
    {
        return $this->belongsTo(DisinfectionSlip::class, 'slip_id')->withTrashed();
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
