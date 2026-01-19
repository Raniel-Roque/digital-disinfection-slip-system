<?php

namespace App\Livewire\SuperAdmin\Admins;

use App\Models\User;
use App\Models\Setting;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class Edit extends Component
{
    public $showModal = false;
    public $userId;
    
    // Form fields
    public $first_name;
    public $middle_name;
    public $last_name;
    
    // Original values for change detection
    public $original_first_name;
    public $original_middle_name;
    public $original_last_name;

    protected $listeners = ['openEditModal' => 'openModal'];

    public function openModal($userId)
    {
        $user = User::findOrFail($userId);
        $this->userId = $userId;
        $this->first_name = $user->first_name;
        $this->middle_name = $user->middle_name;
        $this->last_name = $user->last_name;
        
        // Store original values
        $this->original_first_name = $user->first_name;
        $this->original_middle_name = $user->middle_name;
        $this->original_last_name = $user->last_name;
        
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['userId', 'first_name', 'middle_name', 'last_name', 'original_first_name', 'original_middle_name', 'original_last_name']);
        $this->resetValidation();
    }

    public function getHasChangesProperty()
    {
        if (!$this->userId) {
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

        $user = User::findOrFail($this->userId);
        
        // Check if there are any changes
        $hasChanges = ($user->first_name !== $firstName) ||
                      ($user->middle_name !== $middleName) ||
                      ($user->last_name !== $lastName);
        
        if (!$hasChanges) {
            $this->showModal = false;
            $this->dispatch('toast', message: 'No changes detected.', type: 'info');
            return;
        }
        
        // Capture old values for logging
        $oldValues = $user->only(['first_name', 'middle_name', 'last_name', 'username']);
        
        // Check if first name or last name changed (these affect username)
        $nameChanged = ($user->first_name !== $firstName) || ($user->last_name !== $lastName);
        
        // Prepare update data
        $updateData = [
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
        ];
        
        // Regenerate username if name changed
        if ($nameChanged) {
            $newUsername = $this->generateUsername($firstName, $lastName, $this->userId);
            $updateData['username'] = $newUsername;
        }
        
        $user->update($updateData);
        
        // Refresh user to get updated name
        $user->refresh();
        $adminName = $this->getAdminFullName($user);
        
        // Check if username changed
        $usernameChanged = isset($updateData['username']) && $oldValues['username'] !== $updateData['username'];
        
        // If username changed, invalidate all sessions for this user (force logout)
        if ($usernameChanged) {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();
        }
        
        // Generate description based on what changed
        $changedFields = [];
        if ($user->first_name !== $firstName || $user->last_name !== $lastName) {
            $changedFields[] = "name to \"{$adminName}\"";
        }
        if ($usernameChanged) {
            $changedFields[] = "username";
        }
        $description = !empty($changedFields) ? "Updated " . implode(" and ", $changedFields) : "Updated \"{$adminName}\"";
        
        // Log the update action
        Logger::update(
            User::class,
            $user->id,
            $description,
            $oldValues,
            $updateData
        );

        Cache::forget('admins_all');

        $this->showModal = false;
        $this->reset(['userId', 'first_name', 'middle_name', 'last_name', 'original_first_name', 'original_middle_name', 'original_last_name']);
        
        $this->dispatch('admin-updated');
        $this->dispatch('toast', message: "{$adminName} has been updated successfully.", type: 'success');
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
        return view('livewire.super-admin.admins.edit');
    }
}
