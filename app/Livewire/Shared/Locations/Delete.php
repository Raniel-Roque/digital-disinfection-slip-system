<?php

namespace App\Livewire\Shared\Locations;

use App\Models\Location;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Delete extends Component
{
    public $showModal = false;
    public $locationId;
    public $locationName = '';

    public $config = ['minUserType' => 2];

    protected $listeners = ['openDeleteModal' => 'openModal'];

    public function mount($config = [])
    {
        $this->config = array_merge(['minUserType' => 2], $config);
    }

    public $isDeleting = false;

    public function openModal($locationId)
    {
        $location = Location::findOrFail($locationId);
        $this->locationId = $locationId;
        $this->locationName = $location->location_name;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['locationId', 'locationName']);
    }

    public function delete()
    {
        // Prevent multiple submissions
        if ($this->isDeleting) {
            return;
        }

        $this->isDeleting = true;

        try {
            // Authorization check (superadmin only for delete)
            if (Auth::user()->user_type < 2) {
                abort(403, 'Unauthorized action.');
            }

            $location = Location::findOrFail($this->locationId);
            $locationIdForLog = $location->id;
            $locationName = $location->location_name;
            
            // Capture old values for logging
            $oldValues = $location->only([
                'location_name',
                'photo_id',
                'disabled'
            ]);
            
            // Soft delete the location
            $location->delete();
            
            // Log the delete action
            Logger::delete(
                Location::class,
                $locationIdForLog,
                "Deleted \"{$locationName}\"",
                $oldValues
            );

            Cache::forget('locations_all');

            $this->showModal = false;
            $this->reset(['locationId', 'locationName']);
            $this->dispatch('location-deleted');
            $this->dispatch('toast', message: "{$locationName} has been deleted.", type: 'success');
        } finally {
            $this->isDeleting = false;
        }
    }

    public function render()
    {
        return view('livewire.shared.locations.delete');
    }
}
