<?php

namespace App\Livewire\Shared\Guards;

use App\Models\User;
use App\Models\Setting;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ResetPassword extends Component
{
    public $showModal = false;
    public $userId;
    public $userName = '';
    public $isResetting = false;

    protected $listeners = ['openResetPasswordModal' => 'openModal'];

    public function openModal($userId)
    {
        $user = User::findOrFail($userId);
        $this->userId = $userId;
        $this->userName = $this->getGuardFullName($user);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['userId', 'userName', 'isResetting']);
    }

    public function resetPassword()
    {
        $user = Auth::user();
        
        // Authorization check - allow admin, superadmin, or super guards
        if (!($user->user_type === 1 || $user->user_type === 2 || ($user->user_type === 0 && $user->super_guard))) {
            return $this->redirect('/', navigate: true);
        }

        if ($this->isResetting) {
            return;
        }

        $this->isResetting = true;

        try {
            DB::beginTransaction();

            $user = User::findOrFail($this->userId);
            $defaultPassword = $this->getDefaultGuardPassword();
            $user->password = Hash::make($defaultPassword);
            $user->save();

            Logger::log(
                'update',
                User::class,
                $user->id,
                "Reset password for guard: {$user->username}",
                null,
                null
            );

            DB::commit();

            $this->showModal = false;
            $this->reset(['userId', 'userName']);
            $this->dispatch('guard-password-reset');
            $this->dispatch('toast', message: 'Password reset successfully.', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Failed to reset password: ' . $e->getMessage(), type: 'error');
        } finally {
            $this->isResetting = false;
        }
    }

    public function getDefaultPasswordProperty()
    {
        return $this->getDefaultGuardPassword();
    }

    /**
     * Get default guard password from settings
     */
    private function getDefaultGuardPassword()
    {
        $setting = Setting::where('setting_name', 'default_guard_password')->first();
        
        if ($setting && !empty($setting->value)) {
            return $setting->value;
        }
        
        return 'brookside25';
    }

    /**
     * Get full name of guard
     */
    private function getGuardFullName($user)
    {
        $parts = array_filter([$user->first_name, $user->middle_name, $user->last_name]);
        return implode(' ', $parts);
    }

    public function render()
    {
        return view('livewire.shared.guards.reset-password');
    }
}
