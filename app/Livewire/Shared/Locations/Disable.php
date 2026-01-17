<?php

namespace App\Livewire\Shared\Locations;

use App\Models\Location;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Disable extends Component
{
    public $showModal = false;
    public $locationId;
    public $locationDisabled = false;

    public $config = ['minUserType' => 2];

    protected $listeners = ['openDisableModal' => 'openModal'];

    public function mount($config = [])
    {
        $this->config = array_merge(['minUserType' => 2], $config);
    }

    public $isTogglingStatus = false;

    public function openModal($locationId)
    {
        $location = Location::findOrFail($locationId);
        $this->locationId = $locationId;
        $this->locationDisabled = $location->disabled;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['locationId', 'locationDisabled']);
    }

    public function toggle()
    {
        // Prevent multiple submissions
        if ($this->isTogglingStatus) {
            return;
        }

        $this->isTogglingStatus = true;

        try {
            // Authorization check
            $minUserType = $this->config['minUserType'] ?? 2;
            if (Auth::user()->user_type < $minUserType) {
                abort(403, 'Unauthorized action.');
            }

            // Atomic update: Get current status and update atomically to prevent race conditions
            $location = Location::findOrFail($this->locationId);
            $wasDisabled = $location->disabled;
            $newStatus = !$wasDisabled; // true = disabled, false = enabled
            
            // Atomic update: Only update if the current disabled status matches what we expect
            $updated = Location::where('id', $this->locationId)
                ->where('disabled', $wasDisabled) // Only update if status hasn't changed
                ->update(['disabled' => $newStatus]);
            
            if ($updated === 0) {
                // Status was changed by another process, refresh and show error
                $location->refresh();
                $this->showModal = false;
                $this->reset(['locationId', 'locationDisabled']);
                $this->dispatch('toast', message: 'The location status was changed by another administrator. Please refresh the page.', type: 'error');
                return;
            }
            
            // Refresh location to get updated data
            $location->refresh();

            $locationName = $location->location_name;
            $message = !$wasDisabled ? "{$locationName} has been disabled." : "{$locationName} has been enabled.";

            // Log the status change
            Logger::update(
                Location::class,
                $location->id,
                ucfirst(!$wasDisabled ? 'disabled' : 'enabled') . " \"{$locationName}\"",
                ['disabled' => $wasDisabled],
                ['disabled' => $newStatus]
            );

            Cache::forget('locations_all');

            $this->showModal = false;
            $this->reset(['locationId', 'locationDisabled']);
            $this->dispatch('location-status-toggled');
            $this->dispatch('toast', message: $message, type: 'success');
        } finally {
            $this->isTogglingStatus = false;
        }
    }

    public function render()
    {
        return view('livewire.shared.locations.disable');
    }
}
