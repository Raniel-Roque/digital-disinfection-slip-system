<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class Guards extends Component
{
    use WithPagination;

    public $search = '';
    public $showFilters = false;
    
    // Filter properties
    public $filterCreatedFrom = '';
    public $filterCreatedTo = '';
    
    // Applied filters
    public $appliedCreatedFrom = '';
    public $appliedCreatedTo = '';
    
    public $selectedUserId;
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $showResetPasswordModal = false;
    public $showCreateModal = false;

    // Edit form fields
    public $first_name;
    public $middle_name;
    public $last_name;
    public $username;

    // Reset password fields
    public $new_password;
    public $confirm_password;

    // Create form fields
    public $create_first_name;
    public $create_middle_name;
    public $create_last_name;

    protected $queryString = ['search'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function applyFilters()
    {
        $this->appliedCreatedFrom = $this->filterCreatedFrom;
        $this->appliedCreatedTo = $this->filterCreatedTo;
        $this->showFilters = false;
        $this->resetPage();
    }

    public function removeFilter($filterName)
    {
        if ($filterName === 'createdFrom') {
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
        $this->appliedCreatedFrom = '';
        $this->appliedCreatedTo = '';
        $this->filterCreatedFrom = '';
        $this->filterCreatedTo = '';
        $this->resetPage();
    }

    public function openEditModal($userId)
    {
        $user = User::findOrFail($userId);
        $this->selectedUserId = $userId;
        $this->first_name = $user->first_name;
        $this->middle_name = $user->middle_name;
        $this->last_name = $user->last_name;
        $this->username = $user->username;
        $this->showEditModal = true;
    }

    public function updateUser()
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $this->selectedUserId,
        ]);

        $user = User::findOrFail($this->selectedUserId);
        $user->update([
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
        ]);

        $this->showEditModal = false;
        $this->reset(['selectedUserId', 'first_name', 'middle_name', 'last_name', 'username']);
        $this->dispatch('toast', message: 'Guard updated successfully!', type: 'success');
    }

    public function openDeleteModal($userId)
    {
        $this->selectedUserId = $userId;
        $this->showDeleteModal = true;
    }

    public function deleteUser()
    {
        $user = User::findOrFail($this->selectedUserId);
        $user->delete();

        $this->showDeleteModal = false;
        $this->reset('selectedUserId');
        $this->dispatch('toast', message: 'Guard deleted successfully!', type: 'success');
    }

    public function openResetPasswordModal($userId)
    {
        $this->selectedUserId = $userId;
        $this->showResetPasswordModal = true;
    }

    public function resetPassword()
    {
        $this->validate([
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        $user = User::findOrFail($this->selectedUserId);
        $user->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->showResetPasswordModal = false;
        $this->reset(['selectedUserId', 'new_password', 'confirm_password']);
        $this->dispatch('toast', message: 'Password reset successfully!', type: 'success');
    }

    public function closeModal()
    {
        $this->showEditModal = false;
        $this->showDeleteModal = false;
        $this->showResetPasswordModal = false;
        $this->showCreateModal = false;
        $this->reset(['selectedUserId', 'first_name', 'middle_name', 'last_name', 'username', 'new_password', 'confirm_password', 'create_first_name', 'create_middle_name', 'create_last_name']);
        $this->resetValidation();
    }

    public function openCreateModal()
    {
        $this->reset(['create_first_name', 'create_middle_name', 'create_last_name']);
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    /**
     * Generate unique username based on first name and last name
     * Format: First letter of first name + Full last name
     * If exists, append increment: JDoe, JDoe1, JDoe2, etc.
     */
    private function generateUsername($firstName, $lastName)
    {
        // Trim whitespace from names
        $firstName = trim($firstName);
        $lastName = trim($lastName);

        // Get first letter of first name (uppercase) and full last name
        if (empty($firstName) || empty($lastName)) {
            return '';
        }

        $firstLetter = strtoupper(substr($firstName, 0, 1));
        $username = $firstLetter . $lastName;

        // Check if username exists
        $counter = 0;
        $baseUsername = $username;

        while (User::where('username', $username)->exists()) {
            $counter++;
            $username = $baseUsername . $counter;
        }

        return $username;
    }

    public function createGuard()
    {
        $this->validate([
            'create_first_name' => 'required|string|max:255',
            'create_middle_name' => 'nullable|string|max:255',
            'create_last_name' => 'required|string|max:255',
        ], [], [
            'create_first_name' => 'First Name',
            'create_middle_name' => 'Middle Name',
            'create_last_name' => 'Last Name',
        ]);

        // Trim inputs
        $firstName = trim($this->create_first_name);
        $middleName = !empty($this->create_middle_name) ? trim($this->create_middle_name) : null;
        $lastName = trim($this->create_last_name);

        // Generate unique username
        $username = $this->generateUsername($firstName, $lastName);

        // Create guard with default password
        User::create([
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'username' => $username,
            'user_type' => 0, // Guard
            'password' => Hash::make('brookside25'), // Default password
        ]);

        $this->showCreateModal = false;
        $this->reset(['create_first_name', 'create_middle_name', 'create_last_name']);
        $this->dispatch('toast', message: 'Guard created successfully!', type: 'success');
        $this->resetPage();
    }

    public function render()
    {
        $users = User::where('user_type', 0)
            ->when($this->search, function ($query) {
                $searchTerm = $this->search;
                
                // Check if search term starts with @
                if (str_starts_with($searchTerm, '@')) {
                    // Search only username (remove @ symbol)
                    $cleanedSearchTerm = ltrim($searchTerm, '@');
                    $query->where('username', 'like', '%' . $cleanedSearchTerm . '%');
                } else {
                    // Search only names (first, middle, last, and combinations)
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('first_name', 'like', '%' . $searchTerm . '%')
                          ->orWhere('middle_name', 'like', '%' . $searchTerm . '%')
                          ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                          ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%' . $searchTerm . '%')
                          ->orWhere(DB::raw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name)"), 'like', '%' . $searchTerm . '%');
                    });
                }
            })
            ->when($this->appliedCreatedFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->appliedCreatedFrom);
            })
            ->when($this->appliedCreatedTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->appliedCreatedTo);
            })
            ->orderByRaw("CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN created_at END DESC")
            ->orderBy('first_name', 'asc')
            ->paginate(10);

        $filtersActive = !empty($this->appliedCreatedFrom) || !empty($this->appliedCreatedTo);

        return view('livewire.admin.guards', [
            'users' => $users,
            'filtersActive' => $filtersActive,
        ]);
    }
}