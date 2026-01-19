<?php

namespace App\Livewire\Shared\Guards;

use App\Models\User;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class Restore extends Component
{
    public $showModal = false;
    public $userId;
    public $userName = ''; // Display name for the guard
    public $isRestoring = false;

    // Configuration - minimum user_type required (2 = superadmin only)
    public $minUserType = 2;

    protected $listeners = [
        'openRestoreModal' => 'openModal'
    ];

    public function mount($config = [])
    {
        $this->minUserType = $config['minUserType'] ?? 2;
        $this->showModal = false; // Ensure modal is closed on mount
    }

    public function openModal($userId)
    {
        $user = User::onlyTrashed()->findOrFail($userId);
        
        $this->userId = $userId;
        $this->userName = $this->getGuardFullName($user);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['userId', 'userName', 'isRestoring']);
    }

    /**
     * Get guard's full name formatted
     * 
     * @param \App\Models\User $user
     * @return string
     */
    private function getGuardFullName($user)
    {
        $parts = array_filter([$user->first_name, $user->middle_name, $user->last_name]);
        $name = implode(' ', $parts);
        return $name ?: $user->username;
    }

    public function restore()
    {
        // Prevent multiple submissions
        if ($this->isRestoring) {
            return;
        }

        // Authorization check
        if (Auth::user()->user_type < $this->minUserType) {
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
                $this->dispatch('toast', message: 'This guard was already restored or does not exist. Please refresh the page.', type: 'error');
                return;
            }

            // Now load the restored user
            $user = User::findOrFail($this->userId);

            // Verify the user is a guard (user_type = 0)
            if ($user->user_type !== 0) {
                // Rollback the restore by deleting again
                $user->delete();
                $this->showModal = false;
                $this->reset(['userId', 'userName']);
                $this->dispatch('toast', message: 'Cannot restore this user.', type: 'error');
                return;
            }

            $guardName = $this->getGuardFullName($user);

            // Log the restore action
            Logger::restore(
                User::class,
                $user->id,
                "Restored guard {$guardName}",
            );

            Cache::forget('guards_all');

            $this->showModal = false;
            $this->reset(['userId', 'userName']);
            $this->dispatch('guard-restored');
            $this->dispatch('toast', message: 'Guard restored successfully.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to restore guard: ' . $e->getMessage(), type: 'error');
        } finally {
            $this->isRestoring = false;
        }
    }

    public function render()
    {
        return view('livewire.shared.guards.restore');
    }
}
