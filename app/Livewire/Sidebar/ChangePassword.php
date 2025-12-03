<?php

namespace App\Livewire\Sidebar;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
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
        // First validate current password
        $this->validate([
            'currentPassword' => ['required', 'current_password'],
        ], [
            'currentPassword.current_password' => 'The current password is incorrect.',
        ]);

        // Then validate new password
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
        $user = Auth::user();
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

