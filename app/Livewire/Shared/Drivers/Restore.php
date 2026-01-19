<?php

namespace App\Livewire\Shared\Drivers;

use App\Models\Driver;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Restore extends Component
{
    public $showModal = false;
    public $driverId;
    public $driverName = ''; // Display name for the driver
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

    public function openModal($driverId)
    {
        $driver = Driver::onlyTrashed()->findOrFail($driverId);

        $this->driverId = $driverId;
        $this->driverName = $this->getDriverFullName($driver);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['driverId', 'driverName', 'isRestoring']);
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
            if (!$this->driverId) {
                return;
            }

            // Atomic restore: Only restore if currently deleted to prevent race conditions
            $restored = Driver::onlyTrashed()
                ->where('id', $this->driverId)
                ->update(['deleted_at' => null]);
            
            if ($restored === 0) {
                // Driver was already restored or doesn't exist
                $this->showModal = false;
                $this->reset(['driverId', 'driverName']);
                $this->dispatch('toast', message: 'This driver was already restored or does not exist. Please refresh the page.', type: 'error');
                $this->dispatch('driver-restored'); // Notify parent to refresh
                return;
            }
            
            // Now load the restored driver
            $driver = Driver::findOrFail($this->driverId);
            
            // Log the restore action
            Logger::restore(
                Driver::class,
                $driver->id,
                "Restored driver {$this->getDriverFullName($driver)}"
            );
            
            Cache::forget('drivers_all');

            $this->showModal = false;
            $this->reset(['driverId', 'driverName']);
            $this->dispatch('driver-restored');
            $this->dispatch('toast', message: "{$this->driverName} has been restored successfully.", type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: "Failed to restore {$this->driverName}: " . $e->getMessage(), type: 'error');
        } finally {
            $this->isRestoring = false;
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
        return view('livewire.shared.drivers.restore');
    }
}
