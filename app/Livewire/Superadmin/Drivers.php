<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Driver;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class Drivers extends Component
{
    use WithPagination;

    public $search = '';
    public $showFilters = false;
    
    // Sorting properties - supports multiple columns
    public $sortColumns = ['first_name' => 'asc']; // Default sort by first_name ascending
    
    // Filter properties
    public $filterStatus = null; // null = All Drivers, 0 = Enabled, 1 = Disabled
    public $filterCreatedFrom = '';
    public $filterCreatedTo = '';
    
    // Applied filters
    public $appliedStatus = null; // null = All Drivers, 0 = Enabled, 1 = Disabled
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
        // null/empty = All Drivers, 0 = Enabled, 1 = Disabled
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
    
    public $selectedDriverId;
    public $selectedDriverDisabled = false;
    public $selectedDriverName = '';
    public $showEditModal = false;
    public $showDisableModal = false;
    public $showCreateModal = false;
    public $showDeleteModal = false;

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

    public function openEditModal($driverId)
    {
        $driver = Driver::findOrFail($driverId);
        $this->selectedDriverId = $driverId;
        $this->first_name = $driver->first_name;
        $this->middle_name = $driver->middle_name;
        $this->last_name = $driver->last_name;
        $this->showEditModal = true;
    }

    public function updateDriver()
    {
        // Authorization check
        if (Auth::user()->user_type < 2) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\'-]+$/u'],
            'middle_name' => ['nullable', 'string', 'max:255', 'regex:/^[\p{L}\s\'-]+$/u'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\'-]+$/u'],
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

        $driver = Driver::findOrFail($this->selectedDriverId);
        $driver->update([
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
        ]);

        // Refresh driver to get updated name
        $driver->refresh();
        $driverName = $this->getDriverFullName($driver);

        $this->showEditModal = false;
        $this->reset(['selectedDriverId', 'first_name', 'middle_name', 'last_name']);
        $this->dispatch('toast', message: "{$driverName} has been updated.", type: 'success');
    }

    public function openDisableModal($driverId)
    {
        $driver = Driver::findOrFail($driverId);
        $this->selectedDriverId = $driverId;
        $this->selectedDriverDisabled = $driver->disabled;
        $this->showDisableModal = true;
    }

    public function toggleDriverStatus()
    {
        // Authorization check
        if (Auth::user()->user_type < 2) {
            abort(403, 'Unauthorized action.');
        }

        $driver = Driver::findOrFail($this->selectedDriverId);
        $wasDisabled = $driver->disabled;
        $newStatus = !$wasDisabled; // true = disabled, false = enabled
        $driver->update([
            'disabled' => $newStatus
        ]);

        // Always reset to first page to avoid pagination issues when driver disappears/appears from filtered results
        $this->resetPage();
        
        $driverName = $this->getDriverFullName($driver);
        $message = !$wasDisabled ? "{$driverName} has been disabled." : "{$driverName} has been enabled.";

        $this->showDisableModal = false;
        $this->reset(['selectedDriverId', 'selectedDriverDisabled']);
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function closeModal()
    {
        $this->showEditModal = false;
        $this->showDisableModal = false;
        $this->showCreateModal = false;
        $this->showDeleteModal = false;
        $this->reset(['selectedDriverId', 'selectedDriverDisabled', 'selectedDriverName', 'first_name', 'middle_name', 'last_name', 'create_first_name', 'create_middle_name', 'create_last_name']);
        $this->resetValidation();
    }

    public function openDeleteModal($driverId)
    {
        $driver = Driver::findOrFail($driverId);
        $this->selectedDriverId = $driverId;
        $this->selectedDriverName = $this->getDriverFullName($driver);
        $this->showDeleteModal = true;
    }

    public function deleteDriver()
    {
        // Authorization check
        if (Auth::user()->user_type < 2) {
            abort(403, 'Unauthorized action.');
        }

        $driver = Driver::findOrFail($this->selectedDriverId);
        $driverName = $this->getDriverFullName($driver);
        
        // Soft delete the driver
        $driver->delete();

        $this->showDeleteModal = false;
        $this->reset(['selectedDriverId', 'selectedDriverName']);
        $this->resetPage();
        $this->dispatch('toast', message: "{$driverName} has been deleted.", type: 'success');
    }

    public function openCreateModal()
    {
        $this->reset(['create_first_name', 'create_middle_name', 'create_last_name']);
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    /**
     * Get driver's full name formatted
     * 
     * @param \App\Models\Driver $driver
     * @return string
     */
    private function getDriverFullName($driver)
    {
        $parts = array_filter([$driver->first_name, $driver->middle_name, $driver->last_name]);
        return implode(' ', $parts);
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

    public function createDriver()
    {
        // Authorization check
        if (Auth::user()->user_type < 2) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'create_first_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\'-]+$/u'],
            'create_middle_name' => ['nullable', 'string', 'max:255', 'regex:/^[\p{L}\s\'-]+$/u'],
            'create_last_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\'-]+$/u'],
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

        // Create driver
        $driver = Driver::create([
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'disabled' => false,
        ]);

        $driverName = $this->getDriverFullName($driver);

        $this->showCreateModal = false;
        $this->reset(['create_first_name', 'create_middle_name', 'create_last_name']);
        $this->dispatch('toast', message: "{$driverName} has been created.", type: 'success');
        $this->resetPage();
    }

    public function render()
    {
        $drivers = Driver::when($this->search, function ($query) {
                $searchTerm = $this->search;
                
                // Sanitize search term to prevent SQL injection
                $searchTerm = trim($searchTerm);
                $searchTerm = preg_replace('/[%_]/', '', $searchTerm); // Remove LIKE wildcards for safety
                
                if (empty($searchTerm)) {
                    return;
                }
                
                // Escape special characters for LIKE
                $escapedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                
                // Search only names (first, middle, last, and combinations)
                // Use parameterized CONCAT to prevent SQL injection
                $query->where(function ($q) use ($escapedSearchTerm) {
                    $q->where('first_name', 'like', '%' . $escapedSearchTerm . '%')
                      ->orWhere('middle_name', 'like', '%' . $escapedSearchTerm . '%')
                      ->orWhere('last_name', 'like', '%' . $escapedSearchTerm . '%')
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%'])
                      ->orWhereRaw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%']);
                });
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
                        $query->orderByRaw("CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 0 ELSE 1 END")
                            ->orderByRaw("CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN created_at END DESC")
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

        return view('livewire.superadmin.drivers', [
            'drivers' => $drivers,
            'filtersActive' => $filtersActive,
            'availableStatuses' => $this->availableStatuses,
        ]);
    }
}
