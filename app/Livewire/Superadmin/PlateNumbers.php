<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Truck;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Services\Logger;

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
    public $selectedTruckName = '';
    public $showEditModal = false;
    public $showDisableModal = false;
    public $showCreateModal = false;
    public $showDeleteModal = false;
    public $showDeleted = false; // Toggle to show deleted items

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

    public $original_plate_number;

    public function openEditModal($truckId)
    {
        $truck = Truck::findOrFail($truckId);
        $this->selectedTruckId = $truckId;
        $this->plate_number = $truck->plate_number;
        
        // Store original value for change detection
        $this->original_plate_number = $truck->plate_number;
        
        $this->showEditModal = true;
    }

    public function getHasChangesProperty()
    {
        if (!$this->selectedTruckId) {
            return false;
        }

        $plateNumber = $this->sanitizeAndUppercasePlateNumber($this->plate_number ?? '');

        return $this->original_plate_number !== $plateNumber;
    }

    public function updateTruck()
    {
        // Authorization check
        if (Auth::user()->user_type < 2) {
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
        
        // Check if there are any changes
        if ($truck->plate_number === $plateNumber) {
            $this->dispatch('toast', message: 'No changes detected.', type: 'info');
            return;
        }
        
        // Capture old values for logging
        $oldValues = $truck->only(['plate_number', 'disabled']);
        
        $truck->update([
            'plate_number' => $plateNumber,
        ]);
        
        // Log the update action
        Logger::update(
            Truck::class,
            $truck->id,
            "Updated to \"{$plateNumber}\"",
            $oldValues,
            ['plate_number' => $plateNumber]
        );

        $this->showEditModal = false;
        $this->reset(['selectedTruckId', 'plate_number', 'original_plate_number']);
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
        if (Auth::user()->user_type < 2) {
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

    public function openDeleteModal($truckId)
    {
        $truck = Truck::findOrFail($truckId);
        $this->selectedTruckId = $truckId;
        $this->selectedTruckName = $truck->plate_number;
        $this->showDeleteModal = true;
    }

    public function deleteTruck()
    {
        // Authorization check
        if (Auth::user()->user_type < 2) {
            abort(403, 'Unauthorized action.');
        }

        $truck = Truck::findOrFail($this->selectedTruckId);
        $truckIdForLog = $truck->id;
        $plateNumber = $truck->plate_number;
        
        // Capture old values for logging
        $oldValues = $truck->only([
            'plate_number',
            'disabled'
        ]);
        
        // Soft delete the truck
        $truck->delete();
        
        // Log the delete action
        Logger::delete(
            Truck::class,
            $truckIdForLog,
            "Deleted \"{$plateNumber}\"",
            $oldValues
        );

        $this->showDeleteModal = false;
        $this->reset(['selectedTruckId', 'selectedTruckName']);
        $this->resetPage();
        $this->dispatch('toast', message: "Plate number {$plateNumber} has been deleted.", type: 'success');
    }

    public function closeModal()
    {
        $this->showEditModal = false;
        $this->showDisableModal = false;
        $this->showDeleteModal = false;
        $this->showCreateModal = false;
        $this->reset(['selectedTruckId', 'selectedTruckDisabled', 'selectedTruckName', 'plate_number', 'original_plate_number', 'create_plate_number']);
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
        if (Auth::user()->user_type < 2) {
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
        
        // Log the create action
        Logger::create(
            Truck::class,
            $truck->id,
            "Created \"{$plateNumber}\"",
            $truck->only(['plate_number', 'disabled'])
        );

        $this->showCreateModal = false;
        $this->reset(['create_plate_number']);
        $this->dispatch('toast', message: "Plate number {$plateNumber} has been created.", type: 'success');
        $this->resetPage();
    }

    public function render()
    {
        $query = $this->showDeleted 
            ? Truck::onlyTrashed()
            : Truck::whereNull('deleted_at');
        
        $trucks = $query->when($this->search, function ($query) {
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
            ->when($this->appliedStatus !== null && !$this->showDeleted, function ($query) {
                if ($this->appliedStatus === 0) {
                    // Enabled (disabled = false)
                    $query->where('disabled', false);
                } elseif ($this->appliedStatus === 1) {
                    // Disabled (disabled = true)
                    $query->where('disabled', true);
                }
            })
            // Apply multi-column sorting
            ->when(!empty($this->sortColumns) && !$this->showDeleted, function($query) {
                // Initialize sortColumns if it's not an array
                if (!is_array($this->sortColumns)) {
                    $this->sortColumns = ['plate_number' => 'asc'];
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
            ->when(empty($this->sortColumns) && !$this->showDeleted, function($query) {
                // Default sort if no sorts are set
                $query->orderBy('plate_number', 'asc');
            })
            ->when($this->showDeleted, function ($query) {
                $query->orderBy('deleted_at', 'desc');
            })
            ->paginate(10);

        $filtersActive = $this->appliedStatus !== null || !empty($this->appliedCreatedFrom) || !empty($this->appliedCreatedTo);

        return view('livewire.superadmin.plate-numbers', [
            'trucks' => $trucks,
            'filtersActive' => $filtersActive,
            'availableStatuses' => $this->availableStatuses,
        ]);
    }

    public function getExportData()
    {
        $query = $this->showDeleted 
            ? Truck::onlyTrashed()
            : Truck::whereNull('deleted_at');
        
        return $query->when($this->search, function ($query) {
                $searchTerm = trim($this->search);
                $searchTerm = preg_replace('/[%_]/', '', $searchTerm);
                if (empty($searchTerm)) {
                    return $query;
                }
                $escapedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                $query->where('plate_number', 'like', '%' . $escapedSearchTerm . '%');
                return $query;
            })
            ->when($this->appliedCreatedFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->appliedCreatedFrom);
            })
            ->when($this->appliedCreatedTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->appliedCreatedTo);
            })
            ->when($this->appliedStatus !== null && !$this->showDeleted, function ($query) {
                if ($this->appliedStatus === 0) {
                    $query->where('disabled', false);
                } elseif ($this->appliedStatus === 1) {
                    $query->where('disabled', true);
                }
            })
            ->when(!$this->showDeleted, function ($query) {
                $query->orderBy('plate_number', 'asc');
            })
            ->when($this->showDeleted, function ($query) {
                $query->orderBy('deleted_at', 'desc');
            })
            ->get();
    }

    public function exportCSV()
    {
        if ($this->showDeleted) {
            return;
        }
        
        $data = $this->getExportData();
        $filename = 'plate_numbers_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['Plate Number', 'Status', 'Created Date']);
            
            foreach ($data as $truck) {
                $status = $truck->disabled ? 'Disabled' : 'Enabled';
                fputcsv($file, [
                    $truck->plate_number,
                    $status,
                    $truck->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function toggleDeletedView()
    {
        $this->showDeleted = !$this->showDeleted;
        $this->resetPage();
    }

    public function restorePlateNumber($truckId)
    {
        // Authorization check
        if (Auth::user()->user_type < 2) {
            abort(403, 'Unauthorized action.');
        }

        $truck = Truck::onlyTrashed()->findOrFail($truckId);
        $truck->restore();
        
        $this->dispatch('toast', message: "{$truck->plate_number} has been restored.", type: 'success');
        $this->resetPage();
    }

    public function openPrintView()
    {
        if ($this->showDeleted) {
            return;
        }
        
        $data = $this->getExportData();
        $exportData = $data->map(function($truck) {
            return [
                'plate_number' => $truck->plate_number,
                'disabled' => $truck->disabled,
                'created_at' => $truck->created_at->toIso8601String(),
            ];
        })->toArray();
        
        $filters = [
            'search' => $this->search,
            'status' => $this->appliedStatus,
            'created_from' => $this->appliedCreatedFrom,
            'created_to' => $this->appliedCreatedTo,
        ];
        
        $sorting = $this->sortColumns ?? ['plate_number' => 'asc'];
        
        $token = Str::random(32);
        Session::put("export_data_{$token}", $exportData);
        Session::put("export_filters_{$token}", $filters);
        Session::put("export_sorting_{$token}", $sorting);
        Session::put("export_data_{$token}_expires", now()->addMinutes(10));
        
        $printUrl = route('superadmin.print.plate-numbers', ['token' => $token]);
        
        $this->dispatch('open-print-window', ['url' => $printUrl]);
    }
}
