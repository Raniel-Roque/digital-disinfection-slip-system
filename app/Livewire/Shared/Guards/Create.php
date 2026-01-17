<?php

namespace App\Livewire\Shared\Guards;

use App\Models\User;
use App\Models\Setting;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Create extends Component
{
    public $showModal = false;
    
    // Form fields
    public $first_name;
    public $middle_name;
    public $last_name;
    public $super_guard = false;
    
    // Configuration
    public $showSuperGuardEdit = true;

    protected $listeners = ['openCreateModal' => 'openModal'];

    public function mount($config = [])
    {
        $this->showSuperGuardEdit = $config['showSuperGuardEdit'] ?? true;
    }

    public function openModal()
    {
        $this->reset(['first_name', 'middle_name', 'last_name', 'super_guard']);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['first_name', 'middle_name', 'last_name', 'super_guard']);
    }

    public function create()
    {
        $user = Auth::user();
        
        // Authorization check
        if (!($user->user_type === 1 || $user->user_type === 2 || ($user->user_type === 0 && $user->super_guard))) {
            return $this->redirect('/', navigate: true);
        }

        $this->validate([
            'first_name' => ['required', 'string', 'max:70', 'regex:/^[\p{L}\s\'-]+$/u'],
            'middle_name' => ['nullable', 'string', 'max:70', 'regex:/^[\p{L}\s\'-]+$/u'],
            'last_name' => ['required', 'string', 'max:70', 'regex:/^[\p{L}\s\'-]+$/u'],
            'super_guard' => ['boolean'],
        ], [
            'first_name.regex' => 'First name can only contain letters, spaces, hyphens, and apostrophes.',
            'middle_name.regex' => 'Middle name can only contain letters, spaces, hyphens, and apostrophes.',
            'last_name.regex' => 'Last name can only contain letters, spaces, hyphens, and apostrophes.',
        ], [
            'first_name' => 'First Name',
            'middle_name' => 'Middle Name',
            'last_name' => 'Last Name',
        ]);

        try {
            DB::beginTransaction();

            $firstName = $this->sanitizeAndCapitalizeName($this->first_name);
            $middleName = !empty($this->middle_name) ? $this->sanitizeAndCapitalizeName($this->middle_name) : null;
            $lastName = $this->sanitizeAndCapitalizeName($this->last_name);

            // Generate username from first and last name
            // Format: First letter of first name (uppercase) + First word of last name
            // Example: "John Doe" -> "JDoe", "John De Guzman" -> "JDe"
            $firstLetter = strtoupper(substr($firstName, 0, 1));
            // Get first word of last name (handles cases like "De Guzman" or "Apple de apple")
            $lastNameWords = preg_split('/\s+/', $lastName);
            $firstWordOfLastName = $lastNameWords[0];
            $username = $firstLetter . $firstWordOfLastName;

            // Check if username exists and generate unique variant
            $counter = 0;
            $baseUsername = $username;
            while (User::where('username', $username)->exists()) {
                $counter++;
                $username = $baseUsername . $counter;
            }

            $defaultPassword = $this->getDefaultGuardPassword();

            $userData = [
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'username' => $username,
                'password' => Hash::make($defaultPassword),
                'user_type' => 0,
                'disabled' => false,
                'super_guard' => $this->showSuperGuardEdit ? ($this->super_guard ?? false) : false,
            ];

            $newUser = User::create($userData);

            Logger::log(
                'create',
                User::class,
                $newUser->id,
                "Created guard: {$newUser->username}",
                null,
                null
            );

            Cache::forget('guards_all');

            DB::commit();

            $this->showModal = false;
            $this->reset(['first_name', 'middle_name', 'last_name', 'super_guard']);
            $this->dispatch('guard-created');
            $this->dispatch('toast', message: 'Guard created successfully.', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Failed to create guard: ' . $e->getMessage(), type: 'error');
        }
    }

    /**
     * Get default guard password from settings
     */
    private function getDefaultGuardPassword()
    {
        $setting = Setting::where('setting_name', 'default_guard_password')->first();
        
        if ($setting && !empty($setting->value)) {
            return $setting->value;
        }
        
        return 'brookside25';
    }

    /**
     * Sanitize and capitalize name
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
        
        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    public function render()
    {
        return view('livewire.shared.guards.create');
    }
}
