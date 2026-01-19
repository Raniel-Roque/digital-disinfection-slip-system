<?php

namespace App\Livewire\SuperAdmin\Admins;

use App\Models\User;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Delete extends Component
{
    public $showModal = false;
    public $userId;
    public $userName = '';
    public $isDeleting = false;

    protected $listeners = ['openDeleteModal' => 'openModal'];

    public function openModal($userId)
    {
        $user = User::findOrFail($userId);
        $this->userId = $userId;
        $this->userName = $this->getAdminFullName($user);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['userId', 'userName', 'isDeleting']);
    }

    public function delete()
    {
        // Prevent multiple submissions
        if ($this->isDeleting) {
            return;
        }

        // Authorization check
        if (Auth::user()->user_type < 2) {
            abort(403, 'Unauthorized action.');
        }

        $this->isDeleting = true;

        try {
            $user = User::findOrFail($this->userId);
            $userIdForLog = $user->id;
            $adminName = $this->getAdminFullName($user);
            
            // Capture old values for logging
            $oldValues = $user->only([
                'first_name',
                'middle_name',
                'last_name',
                'username',
                'user_type',
                'disabled'
            ]);
            
            // Soft delete the user
            $user->delete();
            
            // Log the delete action
            Logger::delete(
                User::class,
                $userIdForLog,
                "Deleted \"{$adminName}\"",
                $oldValues
            );

            Cache::forget('admins_all');

            $this->showModal = false;
            $this->reset(['userId', 'userName']);
            $this->dispatch('admin-deleted');
            $this->dispatch('toast', message: "{$adminName} has been deleted successfully.", type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: "Failed to delete {$adminName}: " . $e->getMessage(), type: 'error');
        } finally {
            $this->isDeleting = false;
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
        return view('livewire.super-admin.admins.delete');
    }
}
