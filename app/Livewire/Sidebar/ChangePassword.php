<?php

namespace App\Livewire\Sidebar;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ChangePassword extends Component
{
    public $currentPassword = '';
    public $newPassword = '';
    public $newPasswordConfirmation = '';
    public $showSuccess = false;

    public function updatePassword()
    {
        // Validate required fields first
        $this->validate([
            'currentPassword' => ['required'],
            'newPassword' => ['required'],
            'newPasswordConfirmation' => ['required'],
        ]);

        $user = Auth::user();
        $key = 'change-password:' . $user->id;

        // Check current password manually before validation
        if (!Hash::check($this->currentPassword, $user->password)) {
            // Wrong password - apply rate limiting
            $executed = RateLimiter::attempt(
                $key,
                $perMinute = 5, // 5 attempts per minute
                function () {
                    // This callback is only executed if rate limit is not exceeded
                }
            );

            if (!$executed) {
                $seconds = RateLimiter::availableIn($key);
                throw ValidationException::withMessages([
                    'currentPassword' => "Too many incorrect password attempts. Please try again in {$seconds} seconds.",
                ]);
            }

            // Password is wrong - throw validation error
            throw ValidationException::withMessages([
                'currentPassword' => 'The current password is incorrect.',
            ]);
        }

        // Current password is correct - clear rate limiter and proceed with new password validation
        RateLimiter::clear($key);

        // Validate new password (no rate limiting for validation errors)
        $this->validate([
            'newPassword' => [
                'required',
                Password::min(8),
            ],
            'newPasswordConfirmation' => ['required'],
        ]);

        // Manually check password confirmation
        if ($this->newPassword !== $this->newPasswordConfirmation) {
            $this->addError('newPasswordConfirmation', 'The password confirmation does not match.');
            return;
        }

        // Update password
        $user->password = $this->newPassword;
        $user->save();

        // Reset form
        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        
        // Show success message
        $this->showSuccess = true;
        session()->flash('password_changed', 'Password changed successfully.');
    }

    public function render()
    {
        return view('livewire.sidebar.change-password');
    }
}

