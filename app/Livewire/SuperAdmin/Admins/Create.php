<?php

namespace App\Livewire\SuperAdmin\Admins;

use App\Models\User;
use App\Models\Setting;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class Create extends Component
{

    public $showModal = false;
    
    // Form fields
    public $first_name;
    public $middle_name;
    public $last_name;

    protected $listeners = ['openCreateModal' => 'openModal'];

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
        if (Auth::user()->user_type < 2) {
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

        // Generate unique username
        $username = $this->generateUsername($firstName, $lastName);

        // Get default password from settings table
        $defaultPassword = $this->getDefaultAdminPassword();

        // Create admin with default password
        $user = User::create([
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'username' => $username,
            'user_type' => 1, // Admin
            'password' => Hash::make($defaultPassword),
        ]);

        Cache::forget('admins_all');

        $adminName = $this->getAdminFullName($user);
        
        // Log the create action
        Logger::create(
            User::class,
            $user->id,
            "Created \"{$adminName}\"",
            $user->only([
                'first_name',
                'middle_name',
                'last_name',
                'username',
                'user_type'
            ])
        );

        $this->showModal = false;
        $this->reset(['first_name', 'middle_name', 'last_name']);
        $this->dispatch('admin-created');
        $this->dispatch('toast', message: "{$adminName} has been created successfully.", type: 'success');
    }

    /**
     * Get admin's full name formatted
     */
    private function getAdminFullName($user)
    {
        $parts = array_filter([$user->first_name, $user->middle_name, $user->last_name]);
        return implode(' ', $parts);
    }

    /**
     * Get default admin password from settings table
     */
    private function getDefaultAdminPassword()
    {
        $setting = Setting::where('setting_name', 'default_guard_password')->first();
        
        if ($setting && !empty($setting->value)) {
            return $setting->value;
        }
        
        return 'brookside25';
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

    /**
     * Generate unique username based on first name and last name
     */
    private function generateUsername($firstName, $lastName, $excludeUserId = null)
    {
        $firstName = trim($firstName);
        $lastName = trim($lastName);

        if (empty($firstName) || empty($lastName)) {
            return '';
        }

        $firstLetter = strtoupper(substr($firstName, 0, 1));
        $lastNameWords = preg_split('/\s+/', $lastName);
        $firstWordOfLastName = $lastNameWords[0];
        $username = $firstLetter . $firstWordOfLastName;

        $counter = 0;
        $baseUsername = $username;

        while (User::whereRaw('LOWER(username) = ?', [strtolower($username)])
            ->when($excludeUserId, function ($query) use ($excludeUserId) {
                $query->where('id', '!=', $excludeUserId);
            })
            ->exists()) {
            $counter++;
            $username = $baseUsername . $counter;
        }

        return $username;
    }

    public function render()
    {
        return view('livewire.super-admin.admins.create');
    }
}
