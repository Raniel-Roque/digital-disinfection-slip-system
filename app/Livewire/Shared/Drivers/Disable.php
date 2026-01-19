<?php

namespace App\Livewire\Shared\Drivers;

use App\Models\Driver;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Disable extends Component
{
    public $showModal = false;
    public $driverId;
    public $driverName = '';
    public $driverDisabled = false;
    public $isToggling = false;

    // Configuration - minimum user_type required (1 = admin, 2 = superadmin)
    public $minUserType = 2;

    protected $listeners = ['openDisableModal' => 'openModal'];

    public function mount($config = [])
    {
        $this->minUserType = $config['minUserType'] ?? 2;
    }

    public function openModal($driverId)
    {
        $driver = Driver::findOrFail($driverId);
        $this->driverId = $driverId;
        $this->driverName = $this->getDriverFullName($driver);
        $this->driverDisabled = $driver->disabled;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['driverId', 'driverName', 'driverDisabled', 'isToggling']);
    }

    public function toggle()
    {
        // Prevent multiple submissions
        if ($this->isToggling) {
            return;
        }

        // Authorization check - allow super guards OR users with minUserType
        $user = Auth::user();
        $isSuperGuard = ($user->user_type === 0 && $user->super_guard) ?? false;
        if (!$isSuperGuard && $user->user_type < $this->minUserType) {
            abort(403, 'Unauthorized action.');
        }

        $this->isToggling = true;

        try {
            // Atomic update: Get current status and update atomically to prevent race conditions
            $driver = Driver::findOrFail($this->driverId);
            $wasDisabled = $driver->disabled;
            $newStatus = !$wasDisabled; // true = disabled, false = enabled
            
            // Atomic update: Only update if the current disabled status matches what we expect
            $updated = Driver::where('id', $this->driverId)
                ->where('disabled', $wasDisabled) // Only update if status hasn't changed
                ->update(['disabled' => $newStatus]);
            
            if ($updated === 0) {
                // Status was changed by another process, refresh and show error
                $driver->refresh();
                $this->showModal = false;
                $this->reset(['driverId', 'driverDisabled']);
                $this->dispatch('toast', message: "The driver status was changed by another administrator. Please refresh the page.", type: 'error');
                return;
            }
            
            // Refresh driver to get updated data
            $driver->refresh();

            $this->driverName = $this->getDriverFullName($driver);
            $message = !$wasDisabled ? "{$this->driverName} has been disabled successfully." : "{$this->driverName} has been enabled successfully.";

            // Log the status change
            Logger::update(
                Driver::class,
                $driver->id,
                ucfirst(!$wasDisabled ? 'disabled' : 'enabled') . " \"{$this->driverName}\"",
                ['disabled' => $wasDisabled],
                ['disabled' => $newStatus]
            );

            Cache::forget('drivers_all');

            $this->showModal = false;
            $this->reset(['driverId', 'driverDisabled']);
            $this->dispatch('driver-status-toggled');
            $this->dispatch('toast', message: $message, type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: "Failed to toggle status for {$this->driverName}: " . $e->getMessage(), type: 'error');
        } finally {
            $this->isToggling = false;
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
        return view('livewire.shared.drivers.disable');
    }
}
