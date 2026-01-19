<?php

namespace App\Livewire\SuperAdmin\Admins;

use App\Models\User;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Disable extends Component
{
    public $showModal = false;
    public $userId;
    public $userDisabled = false;
    public $isToggling = false;

    protected $listeners = ['openDisableModal' => 'openModal'];

    public function openModal($userId)
    {
        $user = User::findOrFail($userId);
        $this->userId = $userId;
        $this->userDisabled = $user->disabled;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['userId', 'userDisabled', 'isToggling']);
    }

    public function toggle()
    {
        // Prevent multiple submissions
        if ($this->isToggling) {
            return;
        }

        // Authorization check
        if (Auth::user()->user_type < 2) {
            abort(403, 'Unauthorized action.');
        }

        $this->isToggling = true;

        try {
            // Atomic update: Get current status and update atomically to prevent race conditions
            $user = User::findOrFail($this->userId);
            $wasDisabled = $user->disabled;
            $newStatus = !$wasDisabled; // true = disabled, false = enabled
            
            // Atomic update: Only update if the current disabled status matches what we expect
            $updated = User::where('id', $this->userId)
                ->where('disabled', $wasDisabled) // Only update if status hasn't changed
                ->update(['disabled' => $newStatus]);
            
            if ($updated === 0) {
                // Status was changed by another process, refresh and show error
                $user->refresh();
                $this->showModal = false;
                $this->reset(['userId', 'userDisabled']);
                $this->dispatch('toast', message: 'The admin status was changed by another administrator. Please refresh the page.', type: 'error');
                return;
            }
            
            $action = $newStatus ? 'disabled' : 'enabled';
            $adminName = $this->getAdminFullName($user);
            
            // Capture old values for logging
            $oldValues = ['disabled' => $wasDisabled];
            
            // Refresh user to get updated data
            $user->refresh();
            
            // Log the status change
            Logger::update(
                User::class,
                $user->id,
                ucfirst($action) . " \"{$adminName}\"",
                $oldValues,
                ['disabled' => $newStatus]
            );

            Cache::forget('admins_all');

            $this->showModal = false;
            $this->reset(['userId', 'userDisabled']);
            
            $this->dispatch('admin-status-toggled');
            $this->dispatch('toast', message: "{$adminName} has been " . ($newStatus ? 'disabled' : 'enabled') . " successfully.", type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: "Failed to toggle status for {$adminName}: " . $e->getMessage(), type: 'error');
        } finally {
            $this->isToggling = false;
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

    public function render()
    {
        return view('livewire.super-admin.admins.disable');
    }
}
