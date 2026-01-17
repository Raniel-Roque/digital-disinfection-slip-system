<?php

namespace App\Livewire\Shared\Guards;

use App\Models\User;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Delete extends Component
{
    public $showModal = false;
    public $userId;
    public $userName = '';

    protected $listeners = ['openDeleteModal' => 'openModal'];

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
        $this->reset(['userId', 'userName']);
    }

    public function delete()
    {
        $user = Auth::user();
        
        // Authorization check - only superadmin can delete
        if ($user->user_type != 2) {
            return $this->redirect('/', navigate: true);
        }

        try {
            DB::beginTransaction();

            $user = User::findOrFail($this->userId);
            $userName = $this->getGuardFullName($user);
            $user->delete();

            Logger::log(
                'delete',
                User::class,
                $user->id,
                "Deleted guard: {$user->username}",
                null,
                null
            );

            Cache::forget('guards_all');

            DB::commit();

            $this->showModal = false;
            $this->reset(['userId', 'userName']);
            $this->dispatch('guard-deleted');
            $this->dispatch('toast', message: "{$userName} has been deleted.", type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->showModal = false;
            $this->dispatch('toast', message: 'Failed to delete guard: ' . $e->getMessage(), type: 'error');
        }
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
        return view('livewire.shared.guards.delete');
    }
}
