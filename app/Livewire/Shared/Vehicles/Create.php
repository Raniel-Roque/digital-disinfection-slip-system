<?php

namespace App\Livewire\Shared\Vehicles;

use App\Models\Vehicle;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Create extends Component
{
    public $showModal = false;
    public $vehicle = '';
    
    // Configuration - minimum user_type required (1 = admin, 2 = superadmin)
    public $minUserType = 1;

    protected $listeners = ['openCreateModal' => 'openModal'];

    public function mount($config = [])
    {
        $this->minUserType = $config['minUserType'] ?? 1;
    }

    public function openModal()
    {
        $this->reset(['vehicle']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['vehicle']);
        $this->resetValidation();
    }

    public function create()
    {
        // Authorization check
        if (Auth::user()->user_type < $this->minUserType) {
            abort(403, 'Unauthorized action.');
        }

        // Sanitize and uppercase input BEFORE validation
        $vehicleValue = $this->sanitizeAndUppercaseVehicle($this->vehicle);
        
        // Basic validation - just ensure it's not empty after sanitization
        if (empty(trim($vehicleValue))) {
            $this->addError('vehicle', 'Vehicle is required.');
            return;
        }

        // Update the property with sanitized value for validation
        $this->vehicle = $vehicleValue;

        // Validate with sanitized value
        $this->validate([
            'vehicle' => ['required', 'string', 'max:20', 'unique:vehicles,vehicle'],
        ], [
            'vehicle.required' => 'Vehicle is required.',
            'vehicle.max' => 'Vehicle must not exceed 20 characters.',
            'vehicle.unique' => 'This vehicle already exists.',
        ], [
            'vehicle' => 'Vehicle',
        ]);

        // Create vehicle
        $vehicle = Vehicle::create([
            'vehicle' => $vehicleValue,
            'disabled' => false,
        ]);

        Cache::forget('vehicles_all');

        // Log the create action
        Logger::create(
            Vehicle::class,
            $vehicle->id,
            "Created \"{$vehicleValue}\"",
            $vehicle->only(['vehicle', 'disabled'])
        );

        $this->showModal = false;
        $this->reset(['vehicle']);
        $this->dispatch('vehicle-created');
        $this->dispatch('toast', message: "Vehicle {$vehicleValue} has been created.", type: 'success');
    }

    /**
     * Sanitize and uppercase vehicle
     */
    private function sanitizeAndUppercaseVehicle($vehicle)
    {
        if (empty($vehicle)) {
            return '';
        }

        // Remove HTML tags and trim whitespace
        $vehicle = strip_tags(trim($vehicle));
        
        // Decode HTML entities
        $vehicle = html_entity_decode($vehicle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove control characters
        $vehicle = preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/u', '', $vehicle);
        
        // Convert dashes to spaces
        $vehicle = str_replace('-', ' ', $vehicle);
        
        // Normalize whitespace
        $vehicle = preg_replace('/\s+/', ' ', $vehicle);
        $vehicle = trim($vehicle);
        
        // Convert to uppercase
        return mb_strtoupper($vehicle, 'UTF-8');
    }

    public function render()
    {
        return view('livewire.shared.vehicles.create');
    }
}
