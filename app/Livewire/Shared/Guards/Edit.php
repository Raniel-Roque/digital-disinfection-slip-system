<?php

namespace App\Livewire\Shared\Guards;

use App\Models\User;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Edit extends Component
{
    public $showModal = false;
    public $userId;
    
    // Form fields
    public $first_name;
    public $middle_name;
    public $last_name;
    public $super_guard = false;
    
    // Original values for change detection
    public $original_first_name;
    public $original_middle_name;
    public $original_last_name;
    public $original_super_guard = false;
    
    // Configuration
    public $showSuperGuardEdit = true;

    protected $listeners = ['openEditModal' => 'openModal'];

    public function mount($config = [])
    {
        $this->showSuperGuardEdit = $config['showSuperGuardEdit'] ?? true;
    }

    public function openModal($userId)
    {
        $user = User::findOrFail($userId);
        $this->userId = $userId;
        $this->first_name = $user->first_name;
        $this->middle_name = $user->middle_name;
        $this->last_name = $user->last_name;
        
        if ($this->showSuperGuardEdit) {
            $this->super_guard = (bool) ($user->super_guard ?? false);
        }
        
        // Store original values
        $this->original_first_name = $user->first_name;
        $this->original_middle_name = $user->middle_name;
        $this->original_last_name = $user->last_name;
        if ($this->showSuperGuardEdit) {
            $this->original_super_guard = (bool) ($user->super_guard ?? false);
        }
        
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['userId', 'first_name', 'middle_name', 'last_name', 'super_guard', 'original_first_name', 'original_middle_name', 'original_last_name', 'original_super_guard']);
    }

    public function getHasChangesProperty()
    {
        if (!$this->userId) {
            return false;
        }

        $firstName = $this->sanitizeAndCapitalizeName($this->first_name ?? '');
        $middleName = !empty($this->middle_name) ? $this->sanitizeAndCapitalizeName($this->middle_name) : null;
        $lastName = $this->sanitizeAndCapitalizeName($this->last_name ?? '');

        $hasNameChanges = ($this->original_first_name !== $firstName) ||
               ($this->original_middle_name !== $middleName) ||
               ($this->original_last_name !== $lastName);

        if ($this->showSuperGuardEdit) {
            $hasSuperGuardChange = $this->original_super_guard !== (bool)$this->super_guard;
            return $hasNameChanges || $hasSuperGuardChange;
        }

        return $hasNameChanges;
    }

    public function update()
    {
        $user = Auth::user();
        
        // Authorization check
        if (!($user->user_type === 1 || $user->user_type === 2 || ($user->user_type === 0 && $user->super_guard))) {
            return $this->redirect('/', navigate: true);
        }

        $validationRules = [
            'first_name' => ['required', 'string', 'max:70', 'regex:/^[\p{L}\s\'-]+$/u'],
            'middle_name' => ['nullable', 'string', 'max:70', 'regex:/^[\p{L}\s\'-]+$/u'],
            'last_name' => ['required', 'string', 'max:70', 'regex:/^[\p{L}\s\'-]+$/u'],
        ];

        if ($this->showSuperGuardEdit) {
            $validationRules['super_guard'] = ['boolean'];
        }

        $this->validate($validationRules, [
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

        try {
            DB::beginTransaction();

            $user = User::findOrFail($this->userId);
            
            $firstName = $this->sanitizeAndCapitalizeName($this->first_name);
            $middleName = !empty($this->middle_name) ? $this->sanitizeAndCapitalizeName($this->middle_name) : null;
            $lastName = $this->sanitizeAndCapitalizeName($this->last_name);

            $oldValues = [
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
            ];

            if ($this->showSuperGuardEdit) {
                $oldValues['super_guard'] = $user->super_guard;
            }

            // Regenerate username if first name or last name changed
            $firstNameChanged = $user->first_name !== $firstName;
            $lastNameChanged = $user->last_name !== $lastName;
            
            if ($firstNameChanged || $lastNameChanged) {
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
                while (User::where('username', $username)
                    ->where('id', '!=', $user->id)
                    ->exists()) {
                    $counter++;
                    $username = $baseUsername . $counter;
                }
                
                $user->username = $username;
            }

            $user->first_name = $firstName;
            $user->middle_name = $middleName;
            $user->last_name = $lastName;

            if ($this->showSuperGuardEdit) {
                $user->super_guard = (bool)$this->super_guard;
            }

            $user->save();

            Logger::log(
                'update',
                User::class,
                $user->id,
                "Updated guard: {$user->username}",
                $oldValues,
                [
                    'first_name' => $user->first_name,
                    'middle_name' => $user->middle_name,
                    'last_name' => $user->last_name,
                    'username' => $user->username,
                    'super_guard' => $this->showSuperGuardEdit ? $user->super_guard : null,
                ]
            );

            Cache::forget('guards_all');

            DB::commit();

            $this->showModal = false;
            $this->reset(['userId', 'first_name', 'middle_name', 'last_name', 'super_guard', 'original_first_name', 'original_middle_name', 'original_last_name', 'original_super_guard']);
            $this->dispatch('guard-updated');
            $this->dispatch('toast', message: 'Guard updated successfully.', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Failed to update guard: ' . $e->getMessage(), type: 'error');
        }
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
        return view('livewire.shared.guards.edit');
    }
}
