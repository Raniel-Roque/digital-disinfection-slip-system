<?php

namespace App\Livewire\Shared\Vehicles;

use App\Models\Vehicle;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Disable extends Component
{
    public $showModal = false;
    public $vehicleId;
    public $vehicleDisabled = false;
    public $isToggling = false;

    // Configuration - minimum user_type required (1 = admin, 2 = superadmin)
    public $minUserType = 1;

    protected $listeners = ['openDisableModal' => 'openModal'];

    public function mount($config = [])
    {
        $this->minUserType = $config['minUserType'] ?? 1;
    }

    public function openModal($vehicleId)
    {
        $vehicle = Vehicle::findOrFail($vehicleId);
        $this->vehicleId = $vehicleId;
        $this->vehicleDisabled = $vehicle->disabled;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['vehicleId', 'vehicleDisabled', 'isToggling']);
    }

    public function toggle()
    {
        // Prevent multiple submissions
        if ($this->isToggling) {
            return;
        }

        // Authorization check
        if (Auth::user()->user_type < $this->minUserType) {
            abort(403, 'Unauthorized action.');
        }

        $this->isToggling = true;

        try {
            // Atomic update: Get current status and update atomically to prevent race conditions
            $vehicle = Vehicle::findOrFail($this->vehicleId);
            $wasDisabled = $vehicle->disabled;
            $newStatus = !$wasDisabled; // true = disabled, false = enabled
            
            // Atomic update: Only update if the current disabled status matches what we expect
            $updated = Vehicle::where('id', $this->vehicleId)
                ->where('disabled', $wasDisabled) // Only update if status hasn't changed
                ->update(['disabled' => $newStatus]);
            
            if ($updated === 0) {
                // Status was changed by another process, refresh and show error
                $vehicle->refresh();
                $this->showModal = false;
                $this->reset(['vehicleId', 'vehicleDisabled']);
                $this->dispatch('toast', message: 'The vehicle status was changed by another administrator. Please refresh the page.', type: 'error');
                return;
            }
            
            // Refresh vehicle to get updated data
            $vehicle->refresh();
            
            $vehicleName = $vehicle->vehicle;
            $message = !$wasDisabled ? "Vehicle {$vehicleName} has been disabled." : "Vehicle {$vehicleName} has been enabled.";

            // Log the status change
            Logger::update(
                Vehicle::class,
                $vehicle->id,
                ucfirst(!$wasDisabled ? 'disabled' : 'enabled') . " vehicle \"{$vehicleName}\"",
                ['disabled' => $wasDisabled],
                ['disabled' => $newStatus]
            );

            Cache::forget('vehicles_all');

            $this->showModal = false;
            $this->reset(['vehicleId', 'vehicleDisabled']);
            $this->dispatch('vehicle-status-toggled');
            $this->dispatch('toast', message: $message, type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to toggle status: ' . $e->getMessage(), type: 'error');
        } finally {
            $this->isToggling = false;
        }
    }

    public function render()
    {
        return view('livewire.shared.vehicles.disable');
    }
}
