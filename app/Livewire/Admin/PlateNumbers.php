<?php

namespace App\Livewire\Admin;

use App\Models\Truck;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class PlateNumbers extends Component
{
    use WithPagination;

    public $search = '';
    public $showFilters = false;
    
    // Sorting properties
    public $sortColumns = ['plate_number' => 'asc']; // Default sort by plate_number ascending
    
    // Filter properties
    public $filterStatus = null; // null = All Plate Numbers, 0 = Enabled, 1 = Disabled
    public $filterCreatedFrom = '';
    public $filterCreatedTo = '';
    
    // Applied filters
    public $appliedStatus = null; // null = All Plate Numbers, 0 = Enabled, 1 = Disabled
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
        // null/empty = All Plate Numbers, 0 = Enabled, 1 = Disabled
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
    
    public $selectedTruckId;
    public $selectedTruckDisabled = false;
    public $showEditModal = false;
    public $showDisableModal = false;
    public $showCreateModal = false;

    // Edit form fields
    public $plate_number;

    // Create form fields
    public $create_plate_number;

    protected $queryString = ['search'];
    
    public function applySort($column)
    {
        // Initialize sortColumns if it's not an array (for backward compatibility)
        if (!is_array($this->sortColumns)) {
            $this->sortColumns = [];
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
        
        // If no sorts remain, default to plate_number ascending
        if (empty($this->sortColumns)) {
            $this->sortColumns = ['plate_number' => 'asc'];
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

    public function openEditModal($truckId)
    {
        $truck = Truck::findOrFail($truckId);
        $this->selectedTruckId = $truckId;
        $this->plate_number = $truck->plate_number;
        $this->showEditModal = true;
    }

    public function updateTruck()
    {
        // Authorization check
        if (Auth::user()->user_type < 1) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'plate_number' => ['required', 'string', 'max:255', 'unique:trucks,plate_number,' . $this->selectedTruckId],
        ], [
            'plate_number.required' => 'Plate number is required.',
            'plate_number.max' => 'Plate number must not exceed 255 characters.',
            'plate_number.unique' => 'This plate number already exists.',
        ], [
            'plate_number' => 'Plate Number',
        ]);

        // Sanitize and uppercase input
        $plateNumber = $this->sanitizeAndUppercasePlateNumber($this->plate_number);

        $truck = Truck::findOrFail($this->selectedTruckId);
        $truck->update([
            'plate_number' => $plateNumber,
        ]);

        $this->showEditModal = false;
        $this->reset(['selectedTruckId', 'plate_number']);
        $this->dispatch('toast', message: "Plate number {$plateNumber} has been updated.", type: 'success');
    }

    public function openDisableModal($truckId)
    {
        $truck = Truck::findOrFail($truckId);
        $this->selectedTruckId = $truckId;
        $this->selectedTruckDisabled = $truck->disabled;
        $this->showDisableModal = true;
    }

    public function toggleTruckStatus()
    {
        // Authorization check
        if (Auth::user()->user_type < 1) {
            abort(403, 'Unauthorized action.');
        }

        $truck = Truck::findOrFail($this->selectedTruckId);
        $wasDisabled = $truck->disabled;
        $newStatus = !$wasDisabled; // true = disabled, false = enabled
        $truck->update([
            'disabled' => $newStatus
        ]);

        // Always reset to first page to avoid pagination issues when truck disappears/appears from filtered results
        $this->resetPage();
        
        $plateNumber = $truck->plate_number;
        $message = !$wasDisabled ? "Plate number {$plateNumber} has been disabled." : "Plate number {$plateNumber} has been enabled.";

        $this->showDisableModal = false;
        $this->reset(['selectedTruckId', 'selectedTruckDisabled']);
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function closeModal()
    {
        $this->showEditModal = false;
        $this->showDisableModal = false;
        $this->showCreateModal = false;
        $this->reset(['selectedTruckId', 'selectedTruckDisabled', 'plate_number', 'create_plate_number']);
        $this->resetValidation();
    }

    public function openCreateModal()
    {
        $this->reset(['create_plate_number']);
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    /**
     * Sanitize and uppercase plate number
     * Removes HTML tags, trims whitespace, and converts to uppercase
     * 
     * @param string $plateNumber
     * @return string
     */
    private function sanitizeAndUppercasePlateNumber($plateNumber)
    {
        if (empty($plateNumber)) {
            return '';
        }

        // Remove HTML tags and trim whitespace
        $plateNumber = strip_tags(trim($plateNumber));
        
        // Decode HTML entities (e.g., &amp; becomes &, &#39; becomes ')
        $plateNumber = html_entity_decode($plateNumber, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove any null bytes and other control characters (except newlines/spaces)
        $plateNumber = preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/u', '', $plateNumber);
        
        // Normalize whitespace (replace multiple spaces with single space)
        $plateNumber = preg_replace('/\s+/', ' ', $plateNumber);
        
        // Trim again after normalization
        $plateNumber = trim($plateNumber);
        
        // Convert to uppercase
        return mb_strtoupper($plateNumber, 'UTF-8');
    }

    public function createTruck()
    {
        // Authorization check
        if (Auth::user()->user_type < 1) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'create_plate_number' => ['required', 'string', 'max:255', 'unique:trucks,plate_number'],
        ], [
            'create_plate_number.required' => 'Plate number is required.',
            'create_plate_number.max' => 'Plate number must not exceed 255 characters.',
            'create_plate_number.unique' => 'This plate number already exists.',
        ], [
            'create_plate_number' => 'Plate Number',
        ]);

        // Sanitize and uppercase input
        $plateNumber = $this->sanitizeAndUppercasePlateNumber($this->create_plate_number);

        // Create truck
        $truck = Truck::create([
            'plate_number' => $plateNumber,
            'disabled' => false,
        ]);

        $this->showCreateModal = false;
        $this->reset(['create_plate_number']);
        $this->dispatch('toast', message: "Plate number {$plateNumber} has been created.", type: 'success');
        $this->resetPage();
    }

    public function render()
    {
        $trucks = Truck::when($this->search, function ($query) {
                $searchTerm = $this->search;
                
                // Sanitize search term to prevent SQL injection
                $searchTerm = trim($searchTerm);
                $searchTerm = preg_replace('/[%_]/', '', $searchTerm); // Remove LIKE wildcards for safety
                
                if (empty($searchTerm)) {
                    return;
                }
                
                // Escape special characters for LIKE
                $escapedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                
                // Search plate number
                $query->where('plate_number', 'like', '%' . $escapedSearchTerm . '%');
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
                    $this->sortColumns = ['plate_number' => 'asc'];
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
                $query->orderBy('plate_number', 'asc');
            })
            ->paginate(10);

        $filtersActive = $this->appliedStatus !== null || !empty($this->appliedCreatedFrom) || !empty($this->appliedCreatedTo);

        return view('livewire.admin.plate-numbers', [
            'trucks' => $trucks,
            'filtersActive' => $filtersActive,
            'availableStatuses' => $this->availableStatuses,
        ]);
    }
}
