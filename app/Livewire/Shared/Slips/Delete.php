<?php

namespace App\Livewire\Shared\Slips;

use App\Models\DisinfectionSlip;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Delete extends Component
{
    public $showModal = false;
    public $slipId;
    public $slipSlipId = ''; // The actual slip_id (like DS-2024-001)
    public $isDeleting = false;

    // Configuration - minimum user_type required (1 = admin, 2 = superadmin)
    public $minUserType = 2;

    protected $listeners = ['openDeleteModal' => 'openModal'];

    public function mount($config = [])
    {
        $this->minUserType = $config['minUserType'] ?? 2;
    }

    public function openModal($slipId)
    {
        $slip = DisinfectionSlip::withTrashed()->with([
            'vehicle' => function($q) { $q->withTrashed(); }
        ])->findOrFail($slipId);
        
        $this->slipId = $slipId;
        $this->slipSlipId = $slip->slip_id;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['slipId', 'slipSlipId', 'isDeleting']);
    }

    public function canDelete($slip)
    {
        if (!$slip) {
            return false;
        }

        // Cannot delete if vehicle is soft-deleted
        if ($slip->vehicle && $slip->vehicle->trashed()) {
            return false;
        }

        // SuperAdmin can delete any slip, including completed ones (unless vehicle is soft-deleted)
        return true;
    }

    public function delete()
    {
        // Prevent multiple submissions
        if ($this->isDeleting) {
            return;
        }

        // Authorization check
        if (Auth::user()->user_type < $this->minUserType) {
            abort(403, 'Unauthorized action.');
        }

        $this->isDeleting = true;

        try {
            $slip = DisinfectionSlip::withTrashed()->findOrFail($this->slipId);
            
            if (!$this->canDelete($slip)) {
                $this->dispatch('toast', message: 'Cannot delete this slip.', type: 'error');
                return;
            }

            $slipIdForLog = $slip->id;
            $slipSlipId = $slip->slip_id;
            
            // Capture old values for logging
            $oldValues = $slip->only([
                'slip_id',
                'vehicle_id',
                'location_id',
                'destination_id',
                'driver_id',
                'hatchery_guard_id',
                'received_guard_id',
                'remarks_for_disinfection',
                'status'
            ]);
            
            // Clean up photos before soft deleting the slip
            $slip->deleteAttachments();
            
            // Atomic delete: Only delete if not already deleted to prevent race conditions
            $deleted = DisinfectionSlip::where('id', '=', $slip->id)
                ->whereNull('deleted_at') // Only delete if not already deleted
                ->update(['deleted_at' => now()]);
            
            if ($deleted === 0) {
                // Slip was already deleted by another process
                $this->showModal = false;
                $this->dispatch('toast', message: 'This slip was already deleted by another administrator. Please refresh the page.', type: 'error');
                return;
            }
            
            // Log the delete action
            Logger::delete(
                DisinfectionSlip::class,
                $slipIdForLog,
                "Deleted slip {$slipSlipId}",
                $oldValues
            );

            $this->showModal = false;
            $this->reset(['slipId', 'slipSlipId']);
            $this->dispatch('slip-deleted');
            $this->dispatch('toast', message: "{$slipSlipId} has been deleted.", type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to delete slip: ' . $e->getMessage(), type: 'error');
        } finally {
            $this->isDeleting = false;
        }
    }

    public function render()
    {
        return view('livewire.shared.slips.delete');
    }
}
