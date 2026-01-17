<?php

namespace App\Livewire\Shared\Vehicles;

use App\Models\Vehicle;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Edit extends Component
{
    public $showModal = false;
    public $vehicleId;
    public $vehicle = '';
    public $original_vehicle = '';

    // Configuration - minimum user_type required (1 = admin, 2 = superadmin)
    public $minUserType = 1;

    protected $listeners = ['openEditModal' => 'openModal'];

    public function mount($config = [])
    {
        $this->minUserType = $config['minUserType'] ?? 1;
    }

    public function openModal($vehicleId)
    {
        $vehicle = Vehicle::findOrFail($vehicleId);
        $this->vehicleId = $vehicleId;
        $this->vehicle = $vehicle->vehicle;
        $this->original_vehicle = $vehicle->vehicle;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['vehicleId', 'vehicle', 'original_vehicle']);
        $this->resetValidation();
    }

    public function getHasChangesProperty()
    {
        if (!$this->vehicleId) {
            return false;
        }

        $vehicleValue = $this->sanitizeAndUppercaseVehicle($this->vehicle ?? '');
        return $this->original_vehicle !== $vehicleValue;
    }

    public function update()
    {
        // Authorization check
        if (Auth::user()->user_type < $this->minUserType) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure vehicleId is set
        if (!$this->vehicleId) {
            $this->dispatch('toast', message: 'No vehicle selected.', type: 'error');
            return;
        }

        // Sanitize and uppercase input BEFORE validation
        $vehicleValue = $this->sanitizeAndUppercaseVehicle($this->vehicle ?? '');
        
        // Basic validation - just ensure it's not empty after sanitization
        if (empty(trim($vehicleValue))) {
            $this->addError('vehicle', 'Vehicle is required.');
            return;
        }

        // Update the property with sanitized value for validation
        $this->vehicle = $vehicleValue;

        // Validate with sanitized value
        $this->validate([
            'vehicle' => ['required', 'string', 'max:20', 'unique:vehicles,vehicle,' . $this->vehicleId],
        ], [
            'vehicle.required' => 'Vehicle is required.',
            'vehicle.max' => 'Vehicle must not exceed 20 characters.',
            'vehicle.unique' => 'This vehicle already exists.',
        ], [
            'vehicle' => 'Vehicle',
        ]);

        $vehicle = Vehicle::findOrFail($this->vehicleId);
        
        // Check if there are any changes
        if ($vehicle->vehicle === $vehicleValue) {
            $this->showModal = false;
            $this->dispatch('toast', message: 'No changes detected.', type: 'info');
            return;
        }
        
        // Capture old values for logging
        $oldValues = $vehicle->only(['vehicle', 'disabled']);
        
        $vehicle->update([
            'vehicle' => $vehicleValue,
        ]);
        
        // Log the update action
        Logger::update(
            Vehicle::class,
            $vehicle->id,
            "Updated to \"{$vehicleValue}\"",
            $oldValues,
            ['vehicle' => $vehicleValue]
        );

        Cache::forget('vehicles_all');

        $this->showModal = false;
        $this->reset(['vehicleId', 'vehicle', 'original_vehicle']);
        $this->dispatch('vehicle-updated');
        $this->dispatch('toast', message: "Vehicle {$vehicleValue} has been updated.", type: 'success');
    }

    /**
     * Sanitize and uppercase vehicle
     */
    private function sanitizeAndUppercaseVehicle($vehicle)
    {
        if (empty($vehicle)) {
            return '';
        }

        $vehicle = strip_tags(trim($vehicle));
        $vehicle = html_entity_decode($vehicle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $vehicle = preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/u', '', $vehicle);
        $vehicle = str_replace('-', ' ', $vehicle);
        $vehicle = preg_replace('/\s+/', ' ', $vehicle);
        $vehicle = trim($vehicle);
        
        return mb_strtoupper($vehicle, 'UTF-8');
    }

    public function render()
    {
        return view('livewire.shared.vehicles.edit');
    }
}
