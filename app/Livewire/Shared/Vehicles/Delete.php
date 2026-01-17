<?php

namespace App\Livewire\Shared\Vehicles;

use App\Models\Vehicle;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Delete extends Component
{
    public $showModal = false;
    public $vehicleId;
    public $vehicleName = '';
    public $isDeleting = false;

    protected $listeners = ['openDeleteModal' => 'openModal'];

    public function openModal($vehicleId)
    {
        $vehicle = Vehicle::findOrFail($vehicleId);
        $this->vehicleId = $vehicleId;
        $this->vehicleName = $vehicle->vehicle;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['vehicleId', 'vehicleName', 'isDeleting']);
    }

    public function delete()
    {
        // Prevent multiple submissions
        if ($this->isDeleting) {
            return;
        }

        // Authorization check - only superadmin can delete
        if (Auth::user()->user_type < 2) {
            abort(403, 'Unauthorized action.');
        }

        $this->isDeleting = true;

        try {
            $vehicle = Vehicle::findOrFail($this->vehicleId);
            $vehicleIdForLog = $vehicle->id;
            $vehicleName = $vehicle->vehicle;
            
            // Capture old values for logging
            $oldValues = $vehicle->only([
                'vehicle',
                'disabled'
            ]);
            
            // Soft delete the vehicle
            $vehicle->delete();
            
            // Log the delete action
            Logger::delete(
                Vehicle::class,
                $vehicleIdForLog,
                "Deleted \"{$vehicleName}\"",
                $oldValues
            );

            Cache::forget('vehicles_all');

            $this->showModal = false;
            $this->reset(['vehicleId', 'vehicleName']);
            $this->dispatch('vehicle-deleted');
            $this->dispatch('toast', message: "Vehicle {$vehicleName} has been deleted.", type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to delete vehicle: ' . $e->getMessage(), type: 'error');
        } finally {
            $this->isDeleting = false;
        }
    }

    public function render()
    {
        return view('livewire.shared.vehicles.delete');
    }
}
