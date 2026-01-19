<?php

namespace App\Livewire\SuperAdmin\Admins;

use App\Models\User;
use App\Models\Setting;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ResetPassword extends Component
{
    public $showModal = false;
    public $userId;
    public $isResetting = false;

    protected $listeners = ['openResetPasswordModal' => 'openModal'];

    public function openModal($userId)
    {
        $this->userId = $userId;
        $this->showModal = true; 
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['userId', 'isResetting']);
    }

    public function resetPassword()
    {
        // Prevent multiple submissions
        if ($this->isResetting) {
            return;
        }

        // Authorization check
        if (Auth::user()->user_type < 2) {
            abort(403, 'Unauthorized action.');
        }

        $this->isResetting = true;

        try {
            $user = User::findOrFail($this->userId);
            $defaultPassword = $this->getDefaultAdminPassword();
            $user->update([
                'password' => Hash::make($defaultPassword),
            ]);

            $adminName = $this->getAdminFullName($user);
            
            // Log the password reset as an update
            Logger::update(
                User::class,
                $user->id,
                "Reset password for admin {$adminName}",
                ['password' => '[hidden]'],
                ['password' => '[reset]']
            );

            $this->showModal = false;
            $this->reset('userId');
            $this->dispatch('admin-password-reset');
            $this->dispatch('toast', message: "{$adminName} has been reset successfully.", type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: "Failed to reset password for {$adminName}: " . $e->getMessage(), type: 'error');
        } finally {
            $this->isResetting = false;
        }
    }

    /**
     * Get admin's full name formatted
     */
    private function getAdminFullName($user)
    {
        $parts = array_filter([$user->first_name, $user->middle_name, $user->last_name]);
        return implode(' ', $parts);
    }

    /**
     * Get default admin password from settings table
     */
    private function getDefaultAdminPassword()
    {
        $setting = Setting::where('setting_name', 'default_guard_password')->first();
        
        if ($setting && !empty($setting->value)) {
            return $setting->value;
        }
        
        return 'brookside25';
    }

    public function getDefaultPasswordProperty()
    {
        return $this->getDefaultAdminPassword();
    }

    public function render()
    {
        return view('livewire.super-admin.admins.reset-password');
    }
}
