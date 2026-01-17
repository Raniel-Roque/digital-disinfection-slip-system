<?php

namespace App\Livewire\Shared\Drivers;

use App\Models\Driver;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Create extends Component
{
    public $showModal = false;
    
    // Form fields
    public $first_name;
    public $middle_name;
    public $last_name;

    // Configuration - minimum user_type required (1 = admin, 2 = superadmin)
    public $minUserType = 2;

    protected $listeners = ['openCreateModal' => 'openModal'];

    public function mount($config = [])
    {
        $this->minUserType = $config['minUserType'] ?? 2;
    }

    public function openModal()
    {
        $this->reset(['first_name', 'middle_name', 'last_name']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['first_name', 'middle_name', 'last_name']);
        $this->resetValidation();
    }

    public function create()
    {
        // Authorization check
        if (Auth::user()->user_type < $this->minUserType) {
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

        // Sanitize and capitalize inputs
        $firstName = $this->sanitizeAndCapitalizeName($this->first_name);
        $middleName = !empty($this->middle_name) ? $this->sanitizeAndCapitalizeName($this->middle_name) : null;
        $lastName = $this->sanitizeAndCapitalizeName($this->last_name);

        // Create driver
        $driver = Driver::create([
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'disabled' => false,
        ]);

        Cache::forget('drivers_all');

        $driverName = $this->getDriverFullName($driver);

        // Log the create action
        Logger::create(
            Driver::class,
            $driver->id,
            "Created \"{$driverName}\"",
            $driver->only(['first_name', 'middle_name', 'last_name', 'disabled'])
        );

        $this->showModal = false;
        $this->reset(['first_name', 'middle_name', 'last_name']);
        $this->dispatch('driver-created');
        $this->dispatch('toast', message: "{$driverName} has been created.", type: 'success');
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
        return view('livewire.shared.drivers.create');
    }
}
