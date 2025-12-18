<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'logs';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'user_first_name',
        'user_middle_name',
        'user_last_name',
        'user_username',
        'user_type',
        'action',
        'model_type',
        'model_id',
        'description',
        'changes',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'user_type' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
    
    /**
     * Get the full name of the user who performed the action
     */
    public function getUserFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->user_first_name,
            $this->user_middle_name,
            $this->user_last_name
        ]);
        return implode(' ', $parts);
    }

}

