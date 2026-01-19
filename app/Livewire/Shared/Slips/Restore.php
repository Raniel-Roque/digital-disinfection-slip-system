<?php

namespace App\Livewire\Shared\Slips;

use App\Models\DisinfectionSlip as DisinfectionSlipModel;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Restore extends Component
{
    public $showModal = false;
    public $slipId;
    public $slipSlipId = ''; // The actual slip_id (like DS-2024-001)
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

    public function openModal($slipId)
    {
        $slip = DisinfectionSlipModel::onlyTrashed()->findOrFail($slipId);
        
        $this->slipId = $slipId;
        $this->slipSlipId = $slip->slip_id;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['slipId', 'slipSlipId', 'isRestoring']);
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

        $this->isRestoring = true;

        try {
            if (!$this->slipId) {
                return;
            }

            // Atomic restore: Only restore if currently deleted to prevent race conditions
            // Do the atomic update first, then load the model only if successful
            $restored = DisinfectionSlipModel::onlyTrashed()
                ->where('id', $this->slipId)
                ->update(['deleted_at' => null]);
            
            if ($restored === 0) {
                // Slip was already restored or doesn't exist
                $this->showModal = false;
                $this->reset(['slipId', 'slipSlipId']);
                $this->dispatch('toast', message: 'This slip was already restored or does not exist. Please refresh the page.', type: 'error');
                $this->dispatch('slip-restored'); // Notify parent to refresh
                return;
            }
            
            // Now load the restored slip
            $slip = DisinfectionSlipModel::findOrFail($this->slipId);
            $this->slipSlipId = $slip->slip_id;
            $slipSlipId = $this->slipSlipId;

            // Log the restore action
            Logger::restore(
                DisinfectionSlipModel::class,
                $slip->id,
                "Restored disinfection slip {$slipSlipId}"
            );

            $this->showModal = false;
            $this->reset(['slipId', 'slipSlipId']);
            $this->dispatch('slip-restored');
            $this->dispatch('toast', message: "{$slipSlipId} has been restored.", type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to restore slip: ' . $e->getMessage(), type: 'error');
        } finally {
            $this->isRestoring = false;
        }
    }

    public function render()
    {
        return view('livewire.shared.slips.restore');
    }
}
