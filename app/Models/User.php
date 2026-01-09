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
        'super_guard',
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
     * Superadmins with a location in session are redirected to user dashboard.
     */
    public function dashboardRoute(): string
    {
        // If superadmin is in guard view (has location in session), return user dashboard
        if ($this->isGuardView()) {
            return 'user.dashboard';
        }
        
        return match ((int) $this->user_type) {
            1 => 'admin.dashboard',
            2 => 'superadmin.dashboard',
            default => 'user.dashboard', // includes 0 and null
        };
    }

    /**
     * Check if user should be treated as a guard (user_type 0 or superadmin with location)
     */
    public function isGuardView(): bool
    {
        // Regular guards
        if ($this->user_type === 0) {
            return true;
        }
        
        // Superadmins with location in session should act as guards
        if ($this->user_type === 2 && session()->has('location_id')) {
            return true;
        }
        
        return false;
    }

    /**
     * Get effective user type for view purposes (treats superadmin with location as guard)
     */
    public function effectiveUserType(): int
    {
        if ($this->isGuardView()) {
            return 0;
        }
        
        return (int) $this->user_type;
    }

    /**
     * Get the reports created by this user.
     */
    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}
