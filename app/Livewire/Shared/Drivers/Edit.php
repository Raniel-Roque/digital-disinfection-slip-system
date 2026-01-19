<?php

namespace App\Livewire\Shared\Drivers;

use App\Models\Driver;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Edit extends Component
{
    public $showModal = false;
    public $driverId;
    public $driverName = '';

    // Form fields
    public $first_name;
    public $middle_name;
    public $last_name;
    
    // Original values for change detection
    public $original_first_name;
    public $original_middle_name;
    public $original_last_name;

    // Configuration - minimum user_type required (1 = admin, 2 = superadmin)
    public $minUserType = 2;

    protected $listeners = ['openEditModal' => 'openModal'];

    public function mount($config = [])
    {
        $this->minUserType = $config['minUserType'] ?? 2;
    }

    public function openModal($driverId)
    {
        $driver = Driver::findOrFail($driverId);
        $this->driverId = $driverId;
        $this->driverName = $this->getDriverFullName($driver);
        $this->first_name = $driver->first_name;
        $this->middle_name = $driver->middle_name;
        $this->last_name = $driver->last_name;

        // Store original values
        $this->original_first_name = $driver->first_name;
        $this->original_middle_name = $driver->middle_name;
        $this->original_last_name = $driver->last_name;

        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['driverId', 'driverName', 'first_name', 'middle_name', 'last_name', 'original_first_name', 'original_middle_name', 'original_last_name']);
        $this->resetValidation();
    }

    public function getHasChangesProperty()
    {
        if (!$this->driverId) {
            return false;
        }

        $firstName = $this->sanitizeAndCapitalizeName($this->first_name ?? '');
        $middleName = !empty($this->middle_name) ? $this->sanitizeAndCapitalizeName($this->middle_name) : null;
        $lastName = $this->sanitizeAndCapitalizeName($this->last_name ?? '');

        return ($this->original_first_name !== $firstName) ||
               ($this->original_middle_name !== $middleName) ||
               ($this->original_last_name !== $lastName);
    }

    public function update()
    {
        // Authorization check - allow super guards OR users with minUserType
        $user = Auth::user();
        $isSuperGuard = ($user->user_type === 0 && $user->super_guard) ?? false;
        if (!$isSuperGuard && $user->user_type < $this->minUserType) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'first_name' => ['required', 'string', 'max:70', 'regex:/^[\p{L}\s\'-]+$/u'],
            'middle_name' => ['nullable', 'string', 'max:70', 'regex:/^[\p{L}\s\'-]+$/u'],
            'last_name' => ['required', 'string', 'max:70', 'regex:/^[\p{L}\s\'-]+$/u'],
        ], [
            'first_name.regex' => 'First name can only contain letters, spaces, hyphens, and apostrophes.',
            'middle_name.regex' => 'Middle name can only contain letters, spaces, hyphens, and apostrophes.',
            'last_name.regex' => 'Last name can only contain letters, spaces, hyphens, and apostrophes.',
        ], [
            'first_name' => 'First Name',
            'middle_name' => 'Middle Name',
            'last_name' => 'Last Name',
        ]);

        if (!$this->hasChanges) {
            $this->showModal = false;
            $this->dispatch('toast', message: 'No changes detected.', type: 'info');
            return;
        }

        // Sanitize and capitalize inputs
        $firstName = $this->sanitizeAndCapitalizeName($this->first_name);
        $middleName = !empty($this->middle_name) ? $this->sanitizeAndCapitalizeName($this->middle_name) : null;
        $lastName = $this->sanitizeAndCapitalizeName($this->last_name);

        $driver = Driver::findOrFail($this->driverId);
        
        // Capture old values for logging
        $oldValues = $driver->only(['first_name', 'middle_name', 'last_name']);
        
        $driver->update([
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
        ]);

        Cache::forget('drivers_all');

        // Refresh driver to get updated name
        $driver->refresh();
        $this->driverName = $this->getDriverFullName($driver);

        // Log the update action
        Logger::update(
            Driver::class,
            $driver->id,
            "Updated name to \"{$this->driverName}\"",
            $oldValues,
            ['first_name' => $firstName, 'middle_name' => $middleName, 'last_name' => $lastName]
        );

        $this->showModal = false;
        $this->reset(['driverId', 'first_name', 'middle_name', 'last_name', 'original_first_name', 'original_middle_name', 'original_last_name']);
        $this->dispatch('driver-updated');
        $this->dispatch('toast', message: "{$this->driverName} has been updated successfully.", type: 'success');
    }

    /**
     * Get driver's full name formatted
     */
    private function getDriverFullName($driver)
    {
        $parts = array_filter([$driver->first_name, $driver->middle_name, $driver->last_name]);
        return implode(' ', $parts);
    }

    /**
     * Sanitize and capitalize name (Title Case)
     */
    private function sanitizeAndCapitalizeName($name)
    {
        if (empty($name)) {
            return '';
        }

        $name = strip_tags(trim($name));
        $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $name = preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/u', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = trim($name);
        
        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    public function render()
    {
        return view('livewire.shared.drivers.edit');
    }
}
