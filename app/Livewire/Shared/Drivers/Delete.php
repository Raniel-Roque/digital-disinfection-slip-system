<?php

namespace App\Livewire\Shared\Drivers;

use App\Models\Driver;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Delete extends Component
{
    public $showModal = false;
    public $driverId;
    public $driverName = '';
    public $isDeleting = false;

    // Configuration - minimum user_type required (1 = admin, 2 = superadmin)
    public $minUserType = 2;

    protected $listeners = ['openDeleteModal' => 'openModal'];

    public function mount($config = [])
    {
        $this->minUserType = $config['minUserType'] ?? 2;
    }

    public function openModal($driverId)
    {
        $driver = Driver::findOrFail($driverId);
        $this->driverId = $driverId;
        $this->driverName = $this->getDriverFullName($driver);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['driverId', 'driverName', 'isDeleting']);
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
            $driver = Driver::findOrFail($this->driverId);
            $driverIdForLog = $driver->id;
            $driverName = $this->getDriverFullName($driver);
            
            // Capture old values for logging
            $oldValues = $driver->only(['first_name', 'middle_name', 'last_name', 'disabled']);
            
            // Soft delete the driver
            $driver->delete();
            
            // Log the delete action
            Logger::delete(
                Driver::class,
                $driverIdForLog,
                "Deleted \"{$driverName}\"",
                $oldValues
            );

            Cache::forget('drivers_all');

            $this->showModal = false;
            $this->reset(['driverId', 'driverName']);
            $this->dispatch('driver-deleted');
            $this->dispatch('toast', message: "{$driverName} has been deleted.", type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to delete driver: ' . $e->getMessage(), type: 'error');
        } finally {
            $this->isDeleting = false;
        }
    }

    /**
     * Get driver's full name formatted
     */
    private function getDriverFullName($driver)
    {
        $parts = array_filter([$driver->first_name, $driver->middle_name, $driver->last_name]);
        return implode(' ', $parts);
    }

    public function render()
    {
        return view('livewire.shared.drivers.delete');
    }
}
