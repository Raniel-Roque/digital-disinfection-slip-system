<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'username',
        'password',
        'user_type',
        'disabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'disabled' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure username uniqueness is case-insensitive
        static::creating(function ($user) {
            // Check if a user with the same username (case-insensitive) already exists
            if ($user->username) {
                $existingUser = static::whereRaw('LOWER(username) = ?', [strtolower($user->username)])
                    ->where('id', '!=', $user->id ?? 0)
                    ->first();
                
                if ($existingUser) {
                    throw ValidationException::withMessages([
                        'username' => ['A user with this username already exists (case-insensitive).']
                    ]);
                }
            }
        });

        static::updating(function ($user) {
            // Check if a user with the same username (case-insensitive) already exists
            if ($user->isDirty('username') && $user->username) {
                $existingUser = static::whereRaw('LOWER(username) = ?', [strtolower($user->username)])
                    ->where('id', '!=', $user->id)
                    ->first();
                
                if ($existingUser) {
                    throw ValidationException::withMessages([
                        'username' => ['A user with this username already exists (case-insensitive).']
                    ]);
                }
            }
        });
    }

    /**
     * Resolve the named route for the user's dashboard.
     */
    public function dashboardRoute(): string
    {
        return match ((int) $this->user_type) {
            1 => 'admin.dashboard',
            2 => 'superadmin.dashboard',
            default => 'user.dashboard', // includes 0 and null
        };
    }

    /**
     * Get the reports created by this user.
     */
    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}
