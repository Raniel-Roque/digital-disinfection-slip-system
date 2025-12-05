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

    // Edit form fields
    public $first_name;
    public $middle_name;
    public $last_name;
    public $username;

    // Reset password fields
    public $new_password;
    public $confirm_password;

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
        session()->flash('message', 'Guard updated successfully.');
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
        session()->flash('message', 'Guard deleted successfully.');
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
        session()->flash('message', 'Password reset successfully.');
    }

    public function closeModal()
    {
        $this->showEditModal = false;
        $this->showDeleteModal = false;
        $this->showResetPasswordModal = false;
        $this->reset(['selectedUserId', 'first_name', 'middle_name', 'last_name', 'username', 'new_password', 'confirm_password']);
        $this->resetValidation();
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
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $filtersActive = !empty($this->appliedCreatedFrom) || !empty($this->appliedCreatedTo);

        return view('livewire.admin.guards', [
            'users' => $users,
            'filtersActive' => $filtersActive,
        ]);
    }
}