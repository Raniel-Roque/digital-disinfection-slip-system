<?php

namespace App\Livewire\Shared\Guards;

use App\Models\User;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Disable extends Component
{
    public $showModal = false;
    public $userId;
    public $userDisabled = false;
    public $userName = '';
    public $isToggling = false;

    // Configuration
    public $excludeSuperGuards = false;

    protected $listeners = ['openDisableModal' => 'openModal'];

    public function mount($config = [])
    {
        $this->excludeSuperGuards = $config['excludeSuperGuards'] ?? false;
    }

    public function openModal($userId)
    {
        $user = User::findOrFail($userId);
        $this->userId = $userId;
        $this->userDisabled = $user->disabled;
        $this->userName = $this->getGuardFullName($user);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['userId', 'userDisabled', 'userName', 'isToggling']);
    }

    public function toggle()
    {
        $user = Auth::user();
        
        // Authorization check - allow admin, superadmin, or super guards
        if (!($user->user_type === 1 || $user->user_type === 2 || ($user->user_type === 0 && $user->super_guard))) {
            return $this->redirect('/', navigate: true);
        }

        if ($this->isToggling) {
            return;
        }

        $this->isToggling = true;

        try {
            DB::beginTransaction();

            $user = User::findOrFail($this->userId);
            $oldStatus = $user->disabled;
            $newStatus = !$oldStatus;

            // For super guards, ensure we're not toggling super guards if excludeSuperGuards is true
            if ($this->excludeSuperGuards && $user->super_guard) {
                $this->isToggling = false;
                $this->dispatch('toast', message: 'Cannot toggle status for super guards.', type: 'error');
                return;
            }

            $updated = User::where('id', $this->userId)
                ->where('user_type', 0)
                ->update(['disabled' => $newStatus]);

            if ($updated === 0) {
                throw new \Exception('Guard not found or update failed');
            }

            Logger::log(
                'update',
                User::class,
                $user->id,
                "Toggled guard status: {$user->username}",
                ['disabled' => $oldStatus],
                ['disabled' => $newStatus]
            );

            Cache::forget('guards_all');

            DB::commit();

            $this->showModal = false;
            $this->reset(['userId', 'userDisabled', 'userName']);
            $this->dispatch('guard-status-toggled');
            $this->dispatch('toast', message: "Guard " . ($newStatus ? 'disabled' : 'enabled') . " successfully.", type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Failed to toggle status: ' . $e->getMessage(), type: 'error');
        } finally {
            $this->isToggling = false;
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
        return view('livewire.shared.guards.disable');
    }
}
