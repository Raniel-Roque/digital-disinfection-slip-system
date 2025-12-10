<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\Setting;
use App\Services\Logger;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class Guards extends Component
{
    use WithPagination;

    public $search = '';
    public $showFilters = false;
    
    // Sorting properties - supports multiple columns
    public $sortColumns = ['first_name' => 'asc']; // Default sort by first_name ascending
    
    // Filter properties
    public $filterStatus = null; // null = All Guards, 0 = Enabled, 1 = Disabled
    public $filterCreatedFrom = '';
    public $filterCreatedTo = '';
    
    // Applied filters
    public $appliedStatus = null; // null = All Guards, 0 = Enabled, 1 = Disabled
    public $appliedCreatedFrom = '';
    public $appliedCreatedTo = '';
    
    public $availableStatuses = [
        0 => 'Enabled',
        1 => 'Disabled',
    ];
    
    // Ensure filterStatus is properly typed when updated
    public function updatedFilterStatus($value)
    {
        // Handle null, empty string, or numeric values (0, 1)
        // null/empty = All Guards, 0 = Enabled, 1 = Disabled
        // The select will send values as strings, so we convert to int
        if ($value === null || $value === '' || $value === false) {
            $this->filterStatus = null;
        } elseif (is_numeric($value)) {
            $intValue = (int)$value;
            if ($intValue >= 0 && $intValue <= 1) {
                // Store as integer (0 or 1)
                $this->filterStatus = $intValue;
            } else {
                $this->filterStatus = null;
            }
        } else {
            $this->filterStatus = null;
        }
    }
    
    public $selectedUserId;
    public $selectedUserDisabled = false;
    public $showEditModal = false;
    public $showDisableModal = false;
    public $showResetPasswordModal = false;
    public $showCreateModal = false;

    // Protection flags
    public $isTogglingStatus = false;
    public $isResettingPassword = false;

    // Edit form fields
    public $first_name;
    public $middle_name;
    public $last_name;

    // Create form fields
    public $create_first_name;
    public $create_middle_name;
    public $create_last_name;

    protected $queryString = ['search'];
    
    public function applySort($column)
    {
        // Initialize sortColumns if it's not an array (for backward compatibility)
        if (!is_array($this->sortColumns)) {
            $this->sortColumns = [];
        }
        
        // Special handling: first_name and last_name are mutually exclusive
        if ($column === 'first_name' || $column === 'last_name') {
            // Remove the other name column if it exists
            if ($column === 'first_name') {
                unset($this->sortColumns['last_name']);
            } else {
                unset($this->sortColumns['first_name']);
            }
        }
        
        // If column is already in sort, toggle direction or remove if clicking same direction
        if (isset($this->sortColumns[$column])) {
            if ($this->sortColumns[$column] === 'asc') {
                $this->sortColumns[$column] = 'desc';
            } else {
                // Remove from sort if clicking desc (cycle: asc -> desc -> remove)
                unset($this->sortColumns[$column]);
            }
        } else {
            // Add column with ascending direction
            $this->sortColumns[$column] = 'asc';
        }
        
        // If no sorts remain, default to first_name ascending
        if (empty($this->sortColumns)) {
            $this->sortColumns = ['first_name' => 'asc'];
        }
        
        $this->resetPage();
    }
    
    // Helper method to get sort direction for a column
    public function getSortDirection($column)
    {
        return $this->sortColumns[$column] ?? null;
    }

    /**
     * Get the default guard password (for display in views)
     */
    public function getDefaultPasswordProperty()
    {
        return $this->getDefaultGuardPassword();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function applyFilters()
    {
        $this->appliedStatus = $this->filterStatus;
        $this->appliedCreatedFrom = $this->filterCreatedFrom;
        $this->appliedCreatedTo = $this->filterCreatedTo;
        $this->showFilters = false;
        $this->resetPage();
    }

    public function removeFilter($filterName)
    {
        if ($filterName === 'status') {
            $this->appliedStatus = null;
            $this->filterStatus = null;
        } elseif ($filterName === 'createdFrom') {
            $this->appliedCreatedFrom = '';
            $this->filterCreatedFrom = '';
        } elseif ($filterName === 'createdTo') {
            $this->appliedCreatedTo = '';
            $this->filterCreatedTo = '';
        }
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->appliedStatus = null;
        $this->appliedCreatedFrom = '';
        $this->appliedCreatedTo = '';
        $this->filterStatus = null;
        $this->filterCreatedFrom = '';
        $this->filterCreatedTo = '';
        $this->resetPage();
    }

    public $original_first_name;
    public $original_middle_name;
    public $original_last_name;

    public function openEditModal($userId)
    {
        $user = User::findOrFail($userId);
        $this->selectedUserId = $userId;
        $this->first_name = $user->first_name;
        $this->middle_name = $user->middle_name;
        $this->last_name = $user->last_name;
        
        // Store original values for change detection
        $this->original_first_name = $user->first_name;
        $this->original_middle_name = $user->middle_name;
        $this->original_last_name = $user->last_name;
        
        $this->showEditModal = true;
    }

    public function getHasChangesProperty()
    {
        if (!$this->selectedUserId) {
            return false;
        }

        $firstName = $this->sanitizeAndCapitalizeName($this->first_name ?? '');
        $middleName = !empty($this->middle_name) ? $this->sanitizeAndCapitalizeName($this->middle_name) : null;
        $lastName = $this->sanitizeAndCapitalizeName($this->last_name ?? '');

        return ($this->original_first_name !== $firstName) ||
               ($this->original_middle_name !== $middleName) ||
               ($this->original_last_name !== $lastName);
    }

    public function updateUser()
    {
        // Authorization check
        if (Auth::user()->user_type < 1) {
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

        $user = User::findOrFail($this->selectedUserId);
        
        // Check if there are any changes
        $hasChanges = ($user->first_name !== $firstName) ||
                      ($user->middle_name !== $middleName) ||
                      ($user->last_name !== $lastName);
        
        if (!$hasChanges) {
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
            $newUsername = $this->generateUsername($firstName, $lastName, $this->selectedUserId);
            $updateData['username'] = $newUsername;
        }
        
        $user->update($updateData);
        
        // Refresh user to get updated name
        $user->refresh();
        $guardName = $this->getGuardFullName($user);
        
        // Generate description based on what changed
        $changedFields = [];
        if ($user->first_name !== $firstName || $user->last_name !== $lastName) {
            $changedFields[] = "name to \"{$guardName}\"";
        }
        $newValues = $user->only(['first_name', 'middle_name', 'last_name', 'username']);
        if (isset($newValues['username']) && $oldValues['username'] !== $newValues['username']) {
            $changedFields[] = "username";
        }
        $description = !empty($changedFields) ? "Updated " . implode(" and ", $changedFields) : "Updated \"{$guardName}\"";
        
        // Log the update
        Logger::update(
            User::class,
            $user->id,
            $description,
            $oldValues,
            $newValues
        );

        $this->showEditModal = false;
        $this->reset(['selectedUserId', 'first_name', 'middle_name', 'last_name', 'original_first_name', 'original_middle_name', 'original_last_name']);
        $this->dispatch('toast', message: "{$guardName} has been updated.", type: 'success');
    }

    public function openDisableModal($userId)
    {
        $user = User::findOrFail($userId);
        $this->selectedUserId = $userId;
        $this->selectedUserDisabled = $user->disabled;
        $this->showDisableModal = true;
    }

    public function toggleUserStatus()
    {
        // Prevent multiple submissions
        if ($this->isTogglingStatus) {
            return;
        }

        $this->isTogglingStatus = true;

        try {
        // Authorization check
        if (Auth::user()->user_type < 1) {
            abort(403, 'Unauthorized action.');
        }

        // Atomic update: Get current status and update atomically to prevent race conditions
        $user = User::findOrFail($this->selectedUserId);
        $wasDisabled = $user->disabled;
        $newStatus = !$wasDisabled; // true = disabled, false = enabled
        
        // Atomic update: Only update if the current disabled status matches what we expect
        $updated = User::where('id', $this->selectedUserId)
            ->where('disabled', $wasDisabled) // Only update if status hasn't changed
            ->update(['disabled' => $newStatus]);
        
        if ($updated === 0) {
            // Status was changed by another process, refresh and show error
            $user->refresh();
            $this->showDisableModal = false;
            $this->reset(['selectedUserId', 'selectedUserDisabled']);
            $this->dispatch('toast', message: 'The user status was changed by another administrator. Please refresh the page.', type: 'error');
            return;
        }
        
        $oldValues = ['disabled' => $wasDisabled];
        $newValues = ['disabled' => $newStatus];
        
        // Refresh user to get updated data
        $user->refresh();

        // Always reset to first page to avoid pagination issues when user disappears/appears from filtered results
        $this->resetPage();
        
        $guardName = $this->getGuardFullName($user);
        $message = !$wasDisabled ? "{$guardName} has been disabled." : "{$guardName} has been enabled.";
        
        // Log the status change
        Logger::custom(
            !$wasDisabled ? 'disable' : 'enable',
            !$wasDisabled ? "Disabled guard {$guardName}" : "Enabled guard {$guardName}",
            User::class,
            $user->id,
            ['old_status' => $wasDisabled ? 'enabled' : 'disabled', 'new_status' => $newStatus ? 'disabled' : 'enabled']
        );

        $this->showDisableModal = false;
        $this->reset(['selectedUserId', 'selectedUserDisabled']);
        $this->dispatch('toast', message: $message, type: 'success');
        } finally {
            $this->isTogglingStatus = false;
        }
    }

    public function openResetPasswordModal($userId)
    {
        $this->selectedUserId = $userId;
        $this->showResetPasswordModal = true; 
    }

    public function resetPassword()
    {
        // Prevent multiple submissions
        if ($this->isResettingPassword) {
            return;
        }

        $this->isResettingPassword = true;

        try {
        // Authorization check
        if (Auth::user()->user_type < 1) {
            abort(403, 'Unauthorized action.');
        }

        $user = User::findOrFail($this->selectedUserId);
        $defaultPassword = $this->getDefaultGuardPassword();
        $user->update([
            'password' => Hash::make($defaultPassword),
        ]);

        $guardName = $this->getGuardFullName($user);
        
        // Log the password reset as an update
        Logger::update(
            User::class,
            $user->id,
            "Reset password for guard {$guardName}",
            ['password' => '[hidden]'],
            ['password' => '[reset]']
        );

        $this->showResetPasswordModal = false;
        $this->reset('selectedUserId');
        $this->dispatch('toast', message: "{$guardName}'s password has been reset.", type: 'success');
        } finally {
            $this->isResettingPassword = false;
        }
    }

    public function closeModal()
    {
        $this->showEditModal = false;
        $this->showDisableModal = false;
        $this->showResetPasswordModal = false;
        $this->showCreateModal = false;
        $this->reset(['selectedUserId', 'selectedUserDisabled', 'first_name', 'middle_name', 'last_name', 'original_first_name', 'original_middle_name', 'original_last_name', 'create_first_name', 'create_middle_name', 'create_last_name']);
        $this->resetValidation();
    }

    public function openCreateModal()
    {
        $this->reset(['create_first_name', 'create_middle_name', 'create_last_name']);
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    /**
     * Get guard's full name formatted
     * 
     * @param \App\Models\User $user
     * @return string
     */
    private function getGuardFullName($user)
    {
        $parts = array_filter([$user->first_name, $user->middle_name, $user->last_name]);
        return implode(' ', $parts);
    }

    /**
     * Get default guard password from settings table
     * Falls back to hardcoded value if setting doesn't exist
     * 
     * @return string
     */
    private function getDefaultGuardPassword()
    {
        $setting = Setting::where('setting_name', 'default_guard_password')->first();
        
        if ($setting && !empty($setting->value)) {
            return $setting->value;
        }
        
        // Fallback to default (shouldn't happen if seeded properly)
        return 'brookside25';
    }

    /**
     * Sanitize and capitalize name (Title Case)
     * Removes HTML tags, decodes HTML entities, and converts to proper title case
     * 
     * @param string $name
     * @return string
     */
    private function sanitizeAndCapitalizeName($name)
    {
        if (empty($name)) {
            return '';
        }

        // Remove HTML tags and trim whitespace
        $name = strip_tags(trim($name));
        
        // Decode HTML entities (e.g., &amp; becomes &, &#39; becomes ')
        $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove any null bytes and other control characters (except newlines/spaces)
        $name = preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/u', '', $name);
        
        // Normalize whitespace (replace multiple spaces with single space)
        $name = preg_replace('/\s+/', ' ', $name);
        
        // Trim again after normalization
        $name = trim($name);
        
        // Convert to title case (handles multiple words correctly, including hyphens and apostrophes)
        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Generate unique username based on first name and last name
     * Format: First letter of first name + First word of last name
     * If exists, append increment: JDoe, JDoe1, JDoe2, etc.
     * 
     * @param string $firstName
     * @param string $lastName
     * @param int|null $excludeUserId User ID to exclude from uniqueness check (for updates)
     * @return string
     */
    private function generateUsername($firstName, $lastName, $excludeUserId = null)
    {
        // Trim whitespace from names
        $firstName = trim($firstName);
        $lastName = trim($lastName);

        // Get first letter of first name (uppercase) and first word of last name
        if (empty($firstName) || empty($lastName)) {
            return '';
        }

        $firstLetter = strtoupper(substr($firstName, 0, 1));
        // Get first word of last name (handles cases like "De Guzman" or "Apple de apple")
        $lastNameWords = preg_split('/\s+/', $lastName);
        $firstWordOfLastName = $lastNameWords[0];
        $username = $firstLetter . $firstWordOfLastName;

        // Check if username exists (excluding current user if updating)
        $counter = 0;
        $baseUsername = $username;

        while (User::where('username', $username)
            ->when($excludeUserId, function ($query) use ($excludeUserId) {
                $query->where('id', '!=', $excludeUserId);
            })
            ->exists()) {
            $counter++;
            $username = $baseUsername . $counter;
        }

        return $username;
    }

    public function createGuard()
    {
        // Authorization check
        if (Auth::user()->user_type < 1) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'create_first_name' => ['required', 'string', 'max:70', 'regex:/^[\p{L}\s\'-]+$/u'],
            'create_middle_name' => ['nullable', 'string', 'max:70', 'regex:/^[\p{L}\s\'-]+$/u'],
            'create_last_name' => ['required', 'string', 'max:70', 'regex:/^[\p{L}\s\'-]+$/u'],
        ], [
            'create_first_name.regex' => 'First name can only contain letters, spaces, hyphens, and apostrophes.',
            'create_middle_name.regex' => 'Middle name can only contain letters, spaces, hyphens, and apostrophes.',
            'create_last_name.regex' => 'Last name can only contain letters, spaces, hyphens, and apostrophes.',
        ], [
            'create_first_name' => 'First Name',
            'create_middle_name' => 'Middle Name',
            'create_last_name' => 'Last Name',
        ]);

        // Sanitize and capitalize inputs
        $firstName = $this->sanitizeAndCapitalizeName($this->create_first_name);
        $middleName = !empty($this->create_middle_name) ? $this->sanitizeAndCapitalizeName($this->create_middle_name) : null;
        $lastName = $this->sanitizeAndCapitalizeName($this->create_last_name);

        // Generate unique username
        $username = $this->generateUsername($firstName, $lastName);

        // Get default password from settings table
        $defaultPassword = $this->getDefaultGuardPassword();

        // Create guard with default password
        $user = User::create([
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'username' => $username,
            'user_type' => 0, // Guard
            'password' => Hash::make($defaultPassword),
        ]);

        $guardName = $this->getGuardFullName($user);
        
        // Log the creation
        $newValues = $user->only(['first_name', 'middle_name', 'last_name', 'username', 'user_type']);
        Logger::create(
            User::class,
            $user->id,
            "Created \"{$guardName}\"",
            $newValues
        );

        $this->showCreateModal = false;
        $this->reset(['create_first_name', 'create_middle_name', 'create_last_name']);
        $this->dispatch('toast', message: "{$guardName} has been created.", type: 'success');
        $this->resetPage();
    }

    public function render()
    {
        $users = User::where('user_type', 0)
            ->when($this->search, function ($query) {
                $searchTerm = $this->search;
                
                // Sanitize search term to prevent SQL injection
                $searchTerm = trim($searchTerm);
                $searchTerm = preg_replace('/[%_]/', '', $searchTerm); // Remove LIKE wildcards for safety
                
                if (empty($searchTerm)) {
                    return;
                }
                
                // Escape special characters for LIKE
                $escapedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                
                // Check if search term starts with @
                if (str_starts_with($searchTerm, '@')) {
                    // Search only username (remove @ symbol)
                    $cleanedSearchTerm = ltrim($searchTerm, '@');
                    $escapedCleanedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $cleanedSearchTerm);
                    $query->where('username', 'like', '%' . $escapedCleanedSearchTerm . '%');
                } else {
                    // Search only names (first, middle, last, and combinations)
                    // Use parameterized CONCAT to prevent SQL injection
                    $query->where(function ($q) use ($escapedSearchTerm) {
                        $q->where('first_name', 'like', '%' . $escapedSearchTerm . '%')
                          ->orWhere('middle_name', 'like', '%' . $escapedSearchTerm . '%')
                          ->orWhere('last_name', 'like', '%' . $escapedSearchTerm . '%')
                          ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%'])
                          ->orWhereRaw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%']);
                    });
                }
            })
            ->when($this->appliedCreatedFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->appliedCreatedFrom);
            })
            ->when($this->appliedCreatedTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->appliedCreatedTo);
            })
            ->when($this->appliedStatus !== null, function ($query) {
                if ($this->appliedStatus === 0) {
                    // Enabled (disabled = false)
                    $query->where('disabled', false);
                } elseif ($this->appliedStatus === 1) {
                    // Disabled (disabled = true)
                    $query->where('disabled', true);
                }
            })
            // Apply multi-column sorting
            ->when(!empty($this->sortColumns), function($query) {
                // Initialize sortColumns if it's not an array
                if (!is_array($this->sortColumns)) {
                    $this->sortColumns = ['first_name' => 'asc'];
                }
                
                $firstSort = true;
                foreach ($this->sortColumns as $column => $direction) {
                    if ($column === 'created_at' && $firstSort) {
                        // Special handling for created_at when it's the primary sort
                        // First: prioritize recent records (within 5 minutes) over older ones
                        $query->orderByRaw("CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 0 ELSE 1 END")
                            // Second: sort recent records by created_at DESC, older records also by created_at (to avoid NULL sorting issues)
                            ->orderByRaw("CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN created_at ELSE created_at END DESC")
                            ->orderBy('created_at', $direction);
                    } else {
                        $query->orderBy($column, $direction);
                    }
                    $firstSort = false;
                }
            })
            ->when(empty($this->sortColumns), function($query) {
                // Default sort if no sorts are set
                $query->orderBy('first_name', 'asc');
            })
            ->paginate(10);

        $filtersActive = $this->appliedStatus !== null || !empty($this->appliedCreatedFrom) || !empty($this->appliedCreatedTo);

        return view('livewire.admin.guards', [
            'users' => $users,
            'filtersActive' => $filtersActive,
            'availableStatuses' => $this->availableStatuses,
        ]);
    }

    public function getExportData()
    {
        return User::where('user_type', 0)
            ->when($this->search, function ($query) {
                $searchTerm = $this->search;
                $searchTerm = trim($searchTerm);
                $searchTerm = preg_replace('/[%_]/', '', $searchTerm);
                
                if (empty($searchTerm)) {
                    return;
                }
                
                $escapedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                
                if (str_starts_with($searchTerm, '@')) {
                    $cleanedSearchTerm = ltrim($searchTerm, '@');
                    $escapedCleanedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $cleanedSearchTerm);
                    $query->where('username', 'like', '%' . $escapedCleanedSearchTerm . '%');
                } else {
                    $query->where(function ($q) use ($escapedSearchTerm) {
                        $q->where('first_name', 'like', '%' . $escapedSearchTerm . '%')
                          ->orWhere('middle_name', 'like', '%' . $escapedSearchTerm . '%')
                          ->orWhere('last_name', 'like', '%' . $escapedSearchTerm . '%')
                          ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%'])
                          ->orWhereRaw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%']);
                    });
                }
            })
            ->when($this->appliedCreatedFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->appliedCreatedFrom);
            })
            ->when($this->appliedCreatedTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->appliedCreatedTo);
            })
            ->when($this->appliedStatus !== null, function ($query) {
                if ($this->appliedStatus === 0) {
                    $query->where('disabled', false);
                } elseif ($this->appliedStatus === 1) {
                    $query->where('disabled', true);
                }
            })
            ->orderBy('first_name', 'asc')
            ->orderBy('last_name', 'asc')
            ->get();
    }

    public function exportCSV()
    {
        $data = $this->getExportData();
        $filename = 'guards_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            
            // Headers
            fputcsv($file, ['Name', 'Username', 'Status', 'Created Date']);
            
            // Data
            foreach ($data as $user) {
                $name = trim(implode(' ', array_filter([$user->first_name, $user->middle_name, $user->last_name])));
                $status = $user->disabled ? 'Disabled' : 'Enabled';
                fputcsv($file, [
                    $name,
                    $user->username,
                    $status,
                    $user->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function openPrintView()
    {
        $data = $this->getExportData();
        $exportData = $data->map(function($user) {
            return [
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'disabled' => $user->disabled,
                'created_at' => $user->created_at->toIso8601String(),
            ];
        })->toArray();
        
        $filters = [
            'search' => $this->search,
            'status' => $this->appliedStatus,
            'created_from' => $this->appliedCreatedFrom,
            'created_to' => $this->appliedCreatedTo,
        ];
        
        $sorting = $this->sortColumns ?? ['first_name' => 'asc'];
        
        $token = Str::random(32);
        Session::put("export_data_{$token}", $exportData);
        Session::put("export_filters_{$token}", $filters);
        Session::put("export_sorting_{$token}", $sorting);
        Session::put("export_data_{$token}_expires", now()->addMinutes(10));
        
        $printUrl = route('admin.print.guards', ['token' => $token]);
        
        $this->dispatch('open-print-window', ['url' => $printUrl]);
    }
}