<?php

namespace App\Livewire\SuperAdmin\Admins;

use App\Models\User;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Restore extends Component
{
    public $showModal = false;
    public $userId;
    public $userName = '';
    public $isRestoring = false;

    protected $listeners = ['openRestoreModal' => 'openModal'];

    public function openModal($userId)
    {
        $user = User::onlyTrashed()->findOrFail($userId);
        $this->userId = $userId;
        $this->userName = $this->getAdminFullName($user);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['userId', 'userName', 'isRestoring']);
    }

    public function restore()
    {
        // Prevent multiple submissions
        if ($this->isRestoring) {
            return;
        }

        // Authorization check
        if (Auth::user()->user_type < 2) {
            abort(403, 'Unauthorized action.');
        }

        if (!$this->userId) {
            return;
        }

        $this->isRestoring = true;

        try {
            // Atomic restore: Only restore if currently deleted to prevent race conditions
            $restored = User::onlyTrashed()
                ->where('id', $this->userId)
                ->update(['deleted_at' => null]);
            
            if ($restored === 0) {
                // User was already restored or doesn't exist
                $this->showModal = false;
                $this->reset(['userId', 'userName']);
                $this->dispatch('toast', message: 'This admin was already restored or does not exist. Please refresh the page.', type: 'error');
                return;
            }
            
            // Now load the restored user
            $user = User::findOrFail($this->userId);
            
            // Verify the user is an admin (user_type = 1)
            if ($user->user_type !== 1) {
                // Rollback the restore by deleting again
                $user->delete();
                $this->showModal = false;
                $this->reset(['userId', 'userName']);
                $this->dispatch('toast', message: 'Cannot restore this user.', type: 'error');
                return;
            }

            $adminName = $this->getAdminFullName($user);
            
            // Log the restore action
            Logger::restore(
                User::class,
                $user->id,
                "Restored admin {$adminName}",
            );
            
            Cache::forget('admins_all');

            $this->showModal = false;
            $this->reset(['userId', 'userName']);
            $this->dispatch('admin-restored');
            $this->dispatch('toast', message: "{$adminName} has been restored successfully.", type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: "Failed to restore {$adminName}: " . $e->getMessage(), type: 'error');
        } finally {
            $this->isRestoring = false;
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
        return view('livewire.super-admin.admins.restore');
    }
}
