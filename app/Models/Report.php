<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Logger;

class Report extends Model
{
    use HasFactory, SoftDeletes;

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

        static::created(function ($report) {
            $slipId = $report->slip ? $report->slip->slip_id : null;
            $reportType = $slipId ? "for slip {$slipId}" : "miscellaneous";
            $newValues = $report->only(['user_id', 'slip_id', 'description']);
            Logger::create(
                self::class,
                $report->id,
                "Created report {$reportType}",
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
        return $this->belongsTo(DisinfectionSlip::class, 'slip_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
