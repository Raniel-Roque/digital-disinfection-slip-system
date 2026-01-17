<?php

namespace App\Livewire\Shared;

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
use Illuminate\Support\Facades\Cache;

/**
 * Shared Guards Component
 * 
 * This component can be used by Admin, SuperAdmin, and Super Guards (User/Data)
 * with role-based configuration via the $config property.
 */
class Guards extends Component
{
    use WithPagination;

    // Role-based configuration
    public $config = [
        'role' => 'admin', // 'admin', 'superadmin', or 'superguard'
        'showGuardTypeFilter' => true, // Show guard type filter (Regular/Super)
        'showSuperGuardEdit' => true, // Allow editing super_guard status
        'showRestore' => false, // Show restore functionality
        'excludeSuperGuards' => false, // Exclude super guards from results
        'excludeCurrentUser' => false, // Exclude current user from results
        'printRoute' => 'admin.print.guards', // Route name for print functionality
    ];

    public $search = '';
    public $showFilters = false;
    
    // Sorting properties - supports multiple columns
    public $sortColumns = ['first_name' => 'asc'];
    
    // Filter properties
    public $filterStatus = null;
    public $filterGuardType = null; // Only used if showGuardTypeFilter is true
    public $filterCreatedFrom = '';
    public $filterCreatedTo = '';
    
    // Applied filters
    public $appliedStatus = null;
    public $appliedGuardType = null;
    public $appliedCreatedFrom = '';
    public $appliedCreatedTo = '';
    
    // Store previous date filter values when entering restore mode
    private $previousFilterCreatedFrom = null;
    private $previousFilterCreatedTo = null;
    private $previousAppliedCreatedFrom = null;
    private $previousAppliedCreatedTo = null;
    
    public $availableStatuses = [
        0 => 'Enabled',
        1 => 'Disabled',
    ];
    
    public $availableGuardTypes = [
        0 => 'Regular Guards',
        1 => 'Super Guards',
    ];
    
    // Restore functionality (only for superadmin)
    public $showDeleted = false;
    public $selectedUserId;
    public $selectedUserDisabled = false;
    public $selectedUserName = '';
    public $showRestoreModal = false;
    public $isRestoring = false;

    // Modal states
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
    public $super_guard = false; // Only used if showSuperGuardEdit is true

    // Create form fields
    public $create_first_name;
    public $create_middle_name;
    public $create_last_name;
    public $create_super_guard = false; // Only used if showSuperGuardEdit is true

    // Delete functionality
    public $showDeleteModal = false;

    // Original values for change detection
    public $original_first_name;
    public $original_middle_name;
    public $original_last_name;
    public $original_super_guard = false;

    protected $paginationTheme = 'tailwind';
    protected $queryString = ['search'];

    protected $listeners = [
        'guard-created' => 'handleGuardCreated',
        'guard-updated' => 'handleGuardUpdated',
        'guard-deleted' => 'handleGuardDeleted',
        'guard-status-toggled' => 'handleGuardStatusToggled',
        'guard-password-reset' => 'handleGuardPasswordReset',
    ];

    public function mount($config = [])
    {
        // Merge provided config with defaults
        $this->config = array_merge($this->config, $config);
    }

    public function handleGuardCreated()
    {
        $this->resetPage();
    }

    public function handleGuardUpdated()
    {
        $this->resetPage();
    }

    public function handleGuardDeleted()
    {
        $this->resetPage();
    }

    public function handleGuardStatusToggled()
    {
        $this->resetPage();
    }

    public function handleGuardPasswordReset()
    {
        // No need to reset page for password reset
    }

    // Ensure filterStatus is properly typed when updated
    public function updatedFilterStatus($value)
    {
        if ($value === null || $value === '' || $value === false) {
            $this->filterStatus = null;
        } elseif (is_numeric($value)) {
            $intValue = (int)$value;
            if ($intValue >= 0 && $intValue <= 1) {
                $this->filterStatus = $intValue;
            } else {
                $this->filterStatus = null;
            }
        } else {
            $this->filterStatus = null;
        }
    }
    
    // Ensure filterGuardType is properly typed when updated (only if enabled)
    public function updatedFilterGuardType($value)
    {
        if (!$this->config['showGuardTypeFilter']) {
            return;
        }

        if ($value === null || $value === '' || $value === false) {
            $this->filterGuardType = null;
        } elseif (is_numeric($value)) {
            $intValue = (int)$value;
            if ($intValue >= 0 && $intValue <= 1) {
                $this->filterGuardType = $intValue;
            } else {
                $this->filterGuardType = null;
            }
        } else {
            $this->filterGuardType = null;
        }
    }

    public function applySort($column)
    {
        if (!is_array($this->sortColumns)) {
            $this->sortColumns = [];
        }
        
        // Special handling: first_name and last_name are mutually exclusive
        if ($column === 'first_name' || $column === 'last_name') {
            if ($column === 'first_name') {
                unset($this->sortColumns['last_name']);
            } else {
                unset($this->sortColumns['first_name']);
            }
        }
        
        if (isset($this->sortColumns[$column])) {
            $currentDirection = $this->sortColumns[$column];
            if ($currentDirection === 'asc') {
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
        $this->appliedGuardType = $this->filterGuardType;
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
        } elseif ($filterName === 'guardType' && $this->config['showGuardTypeFilter']) {
            $this->appliedGuardType = null;
            $this->filterGuardType = null;
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
        if ($this->config['showGuardTypeFilter']) {
            $this->appliedGuardType = null;
            $this->filterGuardType = null;
        }
        $this->appliedCreatedFrom = '';
        $this->appliedCreatedTo = '';
        $this->filterStatus = null;
        $this->filterCreatedFrom = '';
        $this->filterCreatedTo = '';
        $this->resetPage();
    }

    public function openEditModal($userId)
    {
        // Dispatch event to the GuardEdit component
        $this->dispatch('openEditModal', $userId);
    }

    public function getHasChangesProperty()
    {
        if (!$this->selectedUserId) {
            return false;
        }

        $firstName = $this->sanitizeAndCapitalizeName($this->first_name ?? '');
        $middleName = !empty($this->middle_name) ? $this->sanitizeAndCapitalizeName($this->middle_name) : null;
        $lastName = $this->sanitizeAndCapitalizeName($this->last_name ?? '');

        $hasNameChanges = ($this->original_first_name !== $firstName) ||
               ($this->original_middle_name !== $middleName) ||
               ($this->original_last_name !== $lastName);

        if ($this->config['showSuperGuardEdit']) {
            $hasSuperGuardChange = $this->original_super_guard !== (bool)$this->super_guard;
            return $hasNameChanges || $hasSuperGuardChange;
        }

        return $hasNameChanges;
    }

    public function updateUser()
    {
        $user = Auth::user();
        
        // Authorization check based on role
        if ($this->config['role'] === 'superguard') {
            if (!(($user->user_type === 0 && $user->super_guard) || $user->user_type === 2)) {
                return $this->redirect('/', navigate: true);
            }
        } elseif ($this->config['role'] === 'admin') {
            if ($user->user_type != 1) {
                return $this->redirect('/', navigate: true);
            }
        } elseif ($this->config['role'] === 'superadmin') {
            if ($user->user_type != 2) {
                return $this->redirect('/', navigate: true);
            }
        }

        $validationRules = [
            'first_name' => ['required', 'string', 'max:70', 'regex:/^[\p{L}\s\'-]+$/u'],
            'middle_name' => ['nullable', 'string', 'max:70', 'regex:/^[\p{L}\s\'-]+$/u'],
            'last_name' => ['required', 'string', 'max:70', 'regex:/^[\p{L}\s\'-]+$/u'],
        ];

        if ($this->config['showSuperGuardEdit']) {
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

        if (!$this->getHasChangesProperty()) {
            $this->showEditModal = false;
            $this->dispatch('toast', message: 'No changes detected.', type: 'info');
            return;
        }

        try {
            DB::beginTransaction();

            $user = User::findOrFail($this->selectedUserId);
            
            $firstName = $this->sanitizeAndCapitalizeName($this->first_name);
            $middleName = !empty($this->middle_name) ? $this->sanitizeAndCapitalizeName($this->middle_name) : null;
            $lastName = $this->sanitizeAndCapitalizeName($this->last_name);

            $oldValues = [
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
            ];

            if ($this->config['showSuperGuardEdit']) {
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

            if ($this->config['showSuperGuardEdit']) {
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
                    'super_guard' => $this->config['showSuperGuardEdit'] ? $user->super_guard : null,
                ]
            );

            Cache::forget('guards_all');

            DB::commit();

            $this->showEditModal = false;
            $this->reset(['selectedUserId', 'first_name', 'middle_name', 'last_name', 'super_guard']);
            $this->resetPage();
            $this->dispatch('toast', message: 'Guard updated successfully.', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Failed to update guard: ' . $e->getMessage(), type: 'error');
        }
    }

    public function closeModal()
    {
        $this->showDisableModal = false;
        $this->showResetPasswordModal = false;
        if ($this->config['showRestore']) {
            $this->showRestoreModal = false;
        }
        $this->reset(['selectedUserId', 'selectedUserDisabled', 'selectedUserName']);
    }

    public function openDisableModal($userId)
    {
        // Dispatch event to the GuardDisable component
        $this->dispatch('openDisableModal', $userId);
    }

    public function toggleUserStatus()
    {
        $user = Auth::user();
        
        // Authorization check
        if ($this->config['role'] === 'superguard') {
            if (!(($user->user_type === 0 && $user->super_guard) || $user->user_type === 2)) {
                return $this->redirect('/', navigate: true);
            }
        } elseif ($this->config['role'] === 'admin') {
            if ($user->user_type != 1) {
                return $this->redirect('/', navigate: true);
            }
        } elseif ($this->config['role'] === 'superadmin') {
            if ($user->user_type != 2) {
                return $this->redirect('/', navigate: true);
            }
        }

        if ($this->isTogglingStatus) {
            return;
        }

        $this->isTogglingStatus = true;

        try {
            DB::beginTransaction();

            $user = User::findOrFail($this->selectedUserId);
            $oldStatus = $user->disabled;
            $newStatus = !$oldStatus;

            // For super guards, ensure we're not toggling super guards if excludeSuperGuards is true
            if ($this->config['excludeSuperGuards'] && $user->super_guard) {
                $this->isTogglingStatus = false;
                $this->dispatch('toast', message: 'Cannot toggle status for super guards.', type: 'error');
                return;
            }

            $updated = User::where('id', $this->selectedUserId)
                ->where('user_type', 0)
                ->update(['disabled' => $newStatus]);

            if ($updated === 0) {
                throw new \Exception('Guard not found or update failed');
            }

            Logger::log(
                'update',
                User::class,
                $user->id,
                "Toggled guard status: {$user->username}",
                ['disabled' => $oldStatus],
                ['disabled' => $newStatus]
            );

            Cache::forget('guards_all');

            DB::commit();

            $this->showDisableModal = false;
            $this->reset(['selectedUserId', 'selectedUserDisabled', 'selectedUserName']);
            $this->resetPage();
            $this->dispatch('toast', message: "Guard " . ($newStatus ? 'disabled' : 'enabled') . " successfully.", type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Failed to toggle status: ' . $e->getMessage(), type: 'error');
        } finally {
            $this->isTogglingStatus = false;
        }
    }

    public function toggleStatus()
    {
        $user = Auth::user();
        
        // Authorization check
        if ($this->config['role'] === 'superguard') {
            if (!(($user->user_type === 0 && $user->super_guard) || $user->user_type === 2)) {
                return $this->redirect('/', navigate: true);
            }
        } elseif ($this->config['role'] === 'admin') {
            if ($user->user_type != 1) {
                return $this->redirect('/', navigate: true);
            }
        } elseif ($this->config['role'] === 'superadmin') {
            if ($user->user_type != 2) {
                return $this->redirect('/', navigate: true);
            }
        }

        if ($this->isTogglingStatus) {
            return;
        }

        $this->isTogglingStatus = true;

        try {
            DB::beginTransaction();

            $user = User::findOrFail($this->selectedUserId);
            $oldStatus = $user->disabled;
            $newStatus = !$oldStatus;

            // For super guards, ensure we're not toggling super guards if excludeSuperGuards is true
            if ($this->config['excludeSuperGuards'] && $user->super_guard) {
                $this->isTogglingStatus = false;
                $this->dispatch('toast', message: 'Cannot toggle status for super guards.', type: 'error');
                return;
            }

            $updated = User::where('id', $this->selectedUserId)
                ->where('user_type', 0)
                ->update(['disabled' => $newStatus]);

            if ($updated === 0) {
                throw new \Exception('Guard not found or update failed');
            }

            Logger::log(
                'update',
                User::class,
                $user->id,
                "Toggled guard status: {$user->username}",
                ['disabled' => $oldStatus],
                ['disabled' => $newStatus]
            );

            Cache::forget('guards_all');

            DB::commit();

            $this->showDisableModal = false;
            $this->reset(['selectedUserId', 'selectedUserDisabled', 'selectedUserName']);
            $this->resetPage();
            $this->dispatch('toast', message: "Guard " . ($newStatus ? 'disabled' : 'enabled') . " successfully.", type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Failed to toggle status: ' . $e->getMessage(), type: 'error');
        } finally {
            $this->isTogglingStatus = false;
        }
    }

    public function openResetPasswordModal($userId)
    {
        // Dispatch event to the GuardResetPassword component
        $this->dispatch('openResetPasswordModal', $userId);
    }

    public function resetPassword()
    {
        $user = Auth::user();
        
        // Authorization check
        if ($this->config['role'] === 'superguard') {
            if (!(($user->user_type === 0 && $user->super_guard) || $user->user_type === 2)) {
                return $this->redirect('/', navigate: true);
            }
        } elseif ($this->config['role'] === 'admin') {
            if ($user->user_type != 1) {
                return $this->redirect('/', navigate: true);
            }
        } elseif ($this->config['role'] === 'superadmin') {
            if ($user->user_type != 2) {
                return $this->redirect('/', navigate: true);
            }
        }

        if ($this->isResettingPassword) {
            return;
        }

        $this->isResettingPassword = true;

        try {
            DB::beginTransaction();

            $user = User::findOrFail($this->selectedUserId);
            $defaultPassword = $this->getDefaultGuardPassword();
            $user->password = Hash::make($defaultPassword);
            $user->save();

            Logger::log(
                'update',
                User::class,
                $user->id,
                "Reset password for guard: {$user->username}",
                null,
                null
            );

            DB::commit();

            $this->showResetPasswordModal = false;
            $this->reset(['selectedUserId', 'selectedUserName']);
            $this->dispatch('toast', message: 'Password reset successfully.', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Failed to reset password: ' . $e->getMessage(), type: 'error');
        } finally {
            $this->isResettingPassword = false;
        }
    }

    public function openCreateModal()
    {
        // Dispatch event to the GuardCreate component
        $this->dispatch('openCreateModal');
    }

    public function openDeleteModal($userId)
    {
        // Dispatch event to the GuardDelete component
        $this->dispatch('openDeleteModal', $userId);
    }

    public function deleteUser()
    {
        $user = Auth::user();
        
        // Authorization check - only superadmin can delete
        if ($this->config['role'] !== 'superadmin') {
            return $this->redirect('/', navigate: true);
        }

        if ($this->config['role'] === 'superadmin') {
            if ($user->user_type != 2) {
                return $this->redirect('/', navigate: true);
            }
        }

        try {
            DB::beginTransaction();

            $user = User::findOrFail($this->selectedUserId);
            $userName = $this->getGuardFullName($user);
            $user->delete();

            Logger::log(
                'delete',
                User::class,
                $user->id,
                "Deleted guard: {$user->username}",
                null,
                null
            );

            Cache::forget('guards_all');

            DB::commit();

            $this->showDeleteModal = false;
            $this->reset(['selectedUserId', 'selectedUserName']);
            $this->resetPage();
            $this->dispatch('toast', message: "{$userName} has been deleted.", type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->showDeleteModal = false;
            $this->dispatch('toast', message: 'Failed to delete guard: ' . $e->getMessage(), type: 'error');
        }
    }

    public function createUser()
    {
        return $this->createGuard();
    }

    public function createGuard()
    {
        $user = Auth::user();
        
        // Authorization check
        if ($this->config['role'] === 'superguard') {
            if (!(($user->user_type === 0 && $user->super_guard) || $user->user_type === 2)) {
                return $this->redirect('/', navigate: true);
            }
        } elseif ($this->config['role'] === 'admin') {
            if ($user->user_type != 1) {
                return $this->redirect('/', navigate: true);
            }
        } elseif ($this->config['role'] === 'superadmin') {
            if ($user->user_type != 2) {
                return $this->redirect('/', navigate: true);
            }
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

        try {
            DB::beginTransaction();

            $firstName = $this->sanitizeAndCapitalizeName($this->create_first_name);
            $middleName = !empty($this->create_middle_name) ? $this->sanitizeAndCapitalizeName($this->create_middle_name) : null;
            $lastName = $this->sanitizeAndCapitalizeName($this->create_last_name);

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
                'super_guard' => false,
            ];

            if ($this->config['showSuperGuardEdit']) {
                $userData['super_guard'] = $this->create_super_guard ?? false;
            }

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

            $this->showCreateModal = false;
            $this->reset(['create_first_name', 'create_middle_name', 'create_last_name', 'create_super_guard']);
            $this->resetPage();
            $this->dispatch('toast', message: 'Guard created successfully.', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Failed to create guard: ' . $e->getMessage(), type: 'error');
        }
    }

    // Restore functionality (only for superadmin)
    public function toggleDeletedView()
    {
        if (!$this->config['showRestore']) {
            return;
        }

        if ($this->showDeleted) {
            // Restoring previous filter values
            if ($this->previousFilterCreatedFrom !== null) {
                $this->filterCreatedFrom = $this->previousFilterCreatedFrom;
            }
            if ($this->previousFilterCreatedTo !== null) {
                $this->filterCreatedTo = $this->previousFilterCreatedTo;
            }
            if ($this->previousAppliedCreatedFrom !== null) {
                $this->appliedCreatedFrom = $this->previousAppliedCreatedFrom;
            }
            if ($this->previousAppliedCreatedTo !== null) {
                $this->appliedCreatedTo = $this->previousAppliedCreatedTo;
            }
        } else {
            // Storing current filter values
            $this->previousFilterCreatedFrom = $this->filterCreatedFrom;
            $this->previousFilterCreatedTo = $this->filterCreatedTo;
            $this->previousAppliedCreatedFrom = $this->appliedCreatedFrom;
            $this->previousAppliedCreatedTo = $this->appliedCreatedTo;
            
            // Clearing date filters when entering restore mode
            $this->filterCreatedFrom = '';
            $this->filterCreatedTo = '';
            $this->appliedCreatedFrom = '';
            $this->appliedCreatedTo = '';
        }

        $this->showDeleted = !$this->showDeleted;
        $this->resetPage();
    }

    public function openRestoreModal($userId)
    {
        if (!$this->config['showRestore']) {
            return;
        }

        $user = User::onlyTrashed()->findOrFail($userId);
        $this->selectedUserId = $userId;
        $this->selectedUserName = $this->getGuardFullName($user);
        $this->showRestoreModal = true;
    }

    public function restoreUser()
    {
        if (!$this->config['showRestore']) {
            return;
        }

        if ($this->isRestoring) {
            return;
        }

        $this->isRestoring = true;

        try {
            DB::beginTransaction();

            $user = User::onlyTrashed()->findOrFail($this->selectedUserId);
            $user->restore();

            Logger::log(
                'restore',
                User::class,
                $user->id,
                "Restored guard {$user->username}",
                null,
                null
            );

            Cache::forget('guards_all');

            $this->showRestoreModal = false;
            $this->reset(['selectedUserId', 'selectedUserName']);
            $this->resetPage();
            $this->dispatch('toast', message: "{$this->selectedUserName} has been restored.", type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Failed to restore guard: ' . $e->getMessage(), type: 'error');
        } finally {
            $this->isRestoring = false;
        }
    }

    public function render()
    {
        $query = $this->config['showRestore'] && $this->showDeleted 
            ? User::onlyTrashed()->where('user_type', 0)
            : User::where('user_type', 0)->whereNull('deleted_at');
        
        $users = $query
            ->when($this->search, function ($query) {
                $searchTerm = trim($this->search);
                $searchTerm = preg_replace('/[%_]/', '', $searchTerm);
                if (empty($searchTerm)) {
                    return;
                }
                $escapedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                $query->where(function($q) use ($escapedSearchTerm) {
                    $q->where('first_name', 'like', '%' . $escapedSearchTerm . '%')
                        ->orWhere('middle_name', 'like', '%' . $escapedSearchTerm . '%')
                        ->orWhere('last_name', 'like', '%' . $escapedSearchTerm . '%')
                        ->orWhere('username', 'like', '%' . $escapedSearchTerm . '%')
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%'])
                        ->orWhereRaw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%']);
                });
            })
            ->when($this->appliedStatus !== null, function($query) {
                $query->where('disabled', $this->appliedStatus);
            })
            ->when($this->config['showGuardTypeFilter'] && $this->appliedGuardType !== null, function($query) {
                if ($this->appliedGuardType === 0) {
                    $query->where('super_guard', false);
                } elseif ($this->appliedGuardType === 1) {
                    $query->where('super_guard', true);
                }
            })
            ->when($this->config['excludeSuperGuards'], function($query) {
                $query->where('super_guard', false);
            })
            ->when($this->config['excludeCurrentUser'], function($query) {
                $query->where('id', '!=', Auth::id());
            })
            ->when($this->appliedCreatedFrom, function($query) {
                $query->whereDate('created_at', '>=', $this->appliedCreatedFrom);
            })
            ->when($this->appliedCreatedTo, function($query) {
                $query->whereDate('created_at', '<=', $this->appliedCreatedTo);
            })
            // Apply multi-column sorting
            ->when(!empty($this->sortColumns), function($query) {
                foreach ($this->sortColumns as $column => $direction) {
                    $query->orderBy($column, $direction);
                }
            })
            ->paginate(15);

        $filtersActive = $this->appliedStatus !== null || 
            ($this->config['showGuardTypeFilter'] && $this->appliedGuardType !== null) || 
            !empty($this->appliedCreatedFrom) || 
            !empty($this->appliedCreatedTo);

        return view('livewire.shared.guards', [
            'users' => $users,
            'filtersActive' => $filtersActive,
            'availableStatuses' => $this->availableStatuses,
            'availableGuardTypes' => $this->availableGuardTypes,
        ]);
    }

    public function getExportData()
    {
        $query = User::where('user_type', 0)->whereNull('deleted_at')
            ->when($this->search, function ($query) {
                $searchTerm = trim($this->search);
                $searchTerm = preg_replace('/[%_]/', '', $searchTerm);
                if (empty($searchTerm)) {
                    return;
                }
                $escapedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                $query->where(function($q) use ($escapedSearchTerm) {
                    $q->where('first_name', 'like', '%' . $escapedSearchTerm . '%')
                        ->orWhere('middle_name', 'like', '%' . $escapedSearchTerm . '%')
                        ->orWhere('last_name', 'like', '%' . $escapedSearchTerm . '%')
                        ->orWhere('username', 'like', '%' . $escapedSearchTerm . '%')
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%'])
                        ->orWhereRaw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%']);
                });
            })
            ->when($this->appliedStatus !== null, function($query) {
                $query->where('disabled', $this->appliedStatus);
            })
            ->when($this->config['showGuardTypeFilter'] && $this->appliedGuardType !== null, function($query) {
                if ($this->appliedGuardType === 0) {
                    $query->where('super_guard', false);
                } elseif ($this->appliedGuardType === 1) {
                    $query->where('super_guard', true);
                }
            })
            ->when($this->config['excludeSuperGuards'], function($query) {
                $query->where('super_guard', false);
            })
            ->when($this->config['excludeCurrentUser'], function($query) {
                $query->where('id', '!=', Auth::id());
            })
            ->when($this->appliedCreatedFrom, function($query) {
                $query->whereDate('created_at', '>=', $this->appliedCreatedFrom);
            })
            ->when($this->appliedCreatedTo, function($query) {
                $query->whereDate('created_at', '<=', $this->appliedCreatedTo);
            })
            ->orderBy('first_name', 'asc')
            ->get();

        return $query;
    }

    public function exportCSV()
    {
        $data = $this->getExportData();
        $filename = 'guards_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'Photo; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['Name', 'Username', 'Status', 'Created Date']);
            
            foreach ($data as $user) {
                fputcsv($file, [
                    trim(implode(' ', array_filter([$user->first_name, $user->middle_name, $user->last_name]))),
                    $user->username,
                    $user->disabled ? 'Disabled' : 'Enabled',
                    $user->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function openPrintView()
    {
        if ($this->config['showRestore'] && $this->showDeleted) {
            return;
        }
        
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
            'guard_type' => $this->config['showGuardTypeFilter'] ? $this->appliedGuardType : null,
            'created_from' => $this->appliedCreatedFrom,
            'created_to' => $this->appliedCreatedTo,
        ];
        
        $sorting = $this->sortColumns ?? ['first_name' => 'asc'];
        
        $token = Str::random(32);
        Session::put("export_data_{$token}", $exportData);
        Session::put("export_filters_{$token}", $filters);
        Session::put("export_sorting_{$token}", $sorting);
        Session::put("export_data_{$token}_expires", now()->addMinutes(10));
        
        $printUrl = route($this->config['printRoute'], ['token' => $token]);
        
        $this->dispatch('open-print-window', ['url' => $printUrl]);
    }

    /**
     * Get full name of guard
     */
    private function getGuardFullName($user)
    {
        $parts = array_filter([$user->first_name, $user->middle_name, $user->last_name]);
        return implode(' ', $parts);
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
}
