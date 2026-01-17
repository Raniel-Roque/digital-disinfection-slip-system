<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Vehicle;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Services\Logger;

class Vehicles extends Component
{
    use WithPagination;

    public $search = '';
    public $showFilters = false;
    
    // Sorting properties
    public $sortColumns = ['vehicle' => 'asc']; // Default sort by vehicle ascending
    
    // Filter properties
    public $filterStatus = null; // null = All Vehicles, 0 = Enabled, 1 = Disabled
    public $filterCreatedFrom = '';
    public $filterCreatedTo = '';
    
    // Applied filters
    public $appliedStatus = null; // null = All Vehicles, 0 = Enabled, 1 = Disabled
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
    
    // Ensure filterStatus is properly typed when updated
    public function updatedFilterStatus($value)
    {
        // Handle null, empty string, or numeric values (0, 1)
        // null/empty = All Vehicles, 0 = Enabled, 1 = Disabled
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
    
    public $selectedVehicleId;
    public $selectedVehicleDisabled = false;
    public $selectedVehicleName = '';
    public $showEditModal = false;
    public $showDisableModal = false;
    public $showCreateModal = false;
    public $showDeleteModal = false;
    public $showRestoreModal = false;
    
    // Protection flags
    public $isTogglingStatus = false;
    public $isDeleting = false;
    public $isRestoring = false;
    public $showDeleted = false; // Toggle to show deleted items

    // Edit form fields
    public $vehicle;

    // Create form fields
    public $create_vehicle;

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
        
        // If no sorts remain, default to vehicle ascending
        if (empty($this->sortColumns)) {
            $this->sortColumns = ['vehicle' => 'asc'];
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

    public $original_vehicle;

    public function openEditModal($vehicleId)
    {
        $vehicle = Vehicle::findOrFail($vehicleId);
        $this->selectedVehicleId = $vehicleId;
        $this->vehicle = $vehicle->vehicle;
        
        // Store original value for change detection
        $this->original_vehicle = $vehicle->vehicle;
        
        $this->showEditModal = true;
    }

    public function getHasChangesProperty()
    {
        if (!$this->selectedVehicleId) {
            return false;
        }

        $vehicle = $this->sanitizeAndUppercaseVehicle($this->vehicle ?? '');

        return $this->original_vehicle !== $vehicle;
    }

    public function updateVehicle()
    {
        // Authorization check
        if (Auth::user()->user_type < 2) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure selectedVehicleId is set
        if (!$this->selectedVehicleId) {
            $this->dispatch('toast', message: 'No vehicle selected.', type: 'error');
            return;
        }

        // Sanitize and uppercase input BEFORE validation
        $vehicle = $this->sanitizeAndUppercaseVehicle($this->vehicle ?? '');
        
        // Basic validation - just ensure it's not empty after sanitization
        if (empty(trim($vehicle))) {
            $this->addError('vehicle', 'Vehicle is required.');
            return;
        }

        // Update the property with sanitized value for validation
        $this->vehicle = $vehicle;

        // Validate with sanitized value
        $this->validate([
            'vehicle' => ['required', 'string', 'max:20', 'unique:vehicles,vehicle,' . $this->selectedVehicleId],
        ], [
            'vehicle.required' => 'Vehicle is required.',
            'vehicle.max' => 'Vehicle must not exceed 20 characters.',
            'vehicle.unique' => 'This vehicle already exists.',
        ], [
            'vehicle' => 'Vehicle',
        ]);

        $vehicle = Vehicle::findOrFail($this->selectedVehicleId);
        
        // Check if there are any changes
        if ($vehicle->vehicle === $vehicle) {
            $this->dispatch('toast', message: 'No changes detected.', type: 'info');
            return;
        }
        
        // Capture old values for logging
        $oldValues = $vehicle->only(['vehicle', 'disabled']);
        
        $vehicle->update([
            'vehicle' => $vehicle,
        ]);
        
        // Log the update action
        Logger::update(
            Vehicle::class,
            $vehicle->id,
            "Updated to \"{$vehicle}\"",
            $oldValues,
            ['vehicle' => $vehicle]
        );

        Cache::forget('vehicles_all');

        $this->showEditModal = false;
        $this->reset(['selectedVehicleId', 'vehicle', 'original_vehicle']);
        $this->dispatch('toast', message: "Vehicle {$vehicle} has been updated.", type: 'success');
    }

    public function openDisableModal($vehicleId)
    {
        $vehicle = Vehicle::findOrFail($vehicleId);
        $this->selectedVehicleId = $vehicleId;
        $this->selectedVehicleDisabled = $vehicle->disabled;
        $this->showDisableModal = true;
    }

    public function toggleVehicleStatus()
    {
        // Prevent multiple submissions
        if ($this->isTogglingStatus) {
            return;
        }

        $this->isTogglingStatus = true;

        try {
        // Authorization check
        if (Auth::user()->user_type < 2) {
            abort(403, 'Unauthorized action.');
        }

        // Atomic update: Get current status and update atomically to prevent race conditions
        $vehicle = Vehicle::findOrFail($this->selectedVehicleId);
        $wasDisabled = $vehicle->disabled;
        $newStatus = !$wasDisabled; // true = disabled, false = enabled
        
        // Atomic update: Only update if the current disabled status matches what we expect
        $updated = Vehicle::where('id', $this->selectedVehicleId)
            ->where('disabled', $wasDisabled) // Only update if status hasn't changed
            ->update(['disabled' => $newStatus]);
        
        if ($updated === 0) {
            // Status was changed by another process, refresh and show error
            $vehicle->refresh();
            $this->showDisableModal = false;
            $this->reset(['selectedVehicleId', 'selectedVehicleDisabled']);
            $this->dispatch('toast', message: 'The vehicle status was changed by another administrator. Please refresh the page.', type: 'error');
            return;
        }
        
        // Refresh vehicle to get updated data
        $vehicle->refresh();

        // Always reset to first page to avoid pagination issues when vehicle disappears/appears from filtered results
        $this->resetPage();
        
        $vehicle = $vehicle->vehicle;
        $message = !$wasDisabled ? "Vehicle {$vehicle} has been disabled." : "Vehicle {$vehicle} has been enabled.";

        // Log the status change
        Logger::update(
            Vehicle::class,
            $vehicle->id,
            ucfirst(!$wasDisabled ? 'disabled' : 'enabled') . " vehicle \"{$vehicle}\"",
            ['disabled' => $wasDisabled],
            ['disabled' => $newStatus]
        );

        Cache::forget('vehicles_all');

        $this->showDisableModal = false;
        $this->reset(['selectedVehicleId', 'selectedVehicleDisabled']);
        $this->dispatch('toast', message: $message, type: 'success');
        } finally {
            $this->isTogglingStatus = false;
        }
    }

    public function openDeleteModal($vehicleId)
    {
        $vehicle = Vehicle::findOrFail($vehicleId);
        $this->selectedVehicleId = $vehicleId;
        $this->selectedVehicleName = $vehicle->vehicle;
        $this->showDeleteModal = true;
    }

    public function deleteVehicle()
    {
        // Prevent multiple submissions
        if ($this->isDeleting) {
            return;
        }

        $this->isDeleting = true;

        try {
        // Authorization check
        if (Auth::user()->user_type < 2) {
            abort(403, 'Unauthorized action.');
        }

        $vehicle = Vehicle::findOrFail($this->selectedVehicleId);
        $vehicleIdForLog = $vehicle->id;
        $vehicle = $vehicle->vehicle;
        
        // Capture old values for logging
        $oldValues = $vehicle->only([
            'vehicle',
            'disabled'
        ]);
        
        // Soft delete the vehicle
        $vehicle->delete();
        
        // Log the delete action
        Logger::delete(
            Vehicle::class,
            $vehicleIdForLog,
            "Deleted \"{$vehicle}\"",
            $oldValues
        );

        Cache::forget('vehicles_all');

        $this->showDeleteModal = false;
        $this->reset(['selectedVehicleId', 'selectedVehicleName']);
        $this->resetPage();
        $this->dispatch('toast', message: "Vehicle {$vehicle} has been deleted.", type: 'success');
        } finally {
            $this->isDeleting = false;
        }
    }

    public function closeModal()
    {
        $this->showEditModal = false;
        $this->showDisableModal = false;
        $this->showDeleteModal = false;
        $this->showCreateModal = false;
        $this->showRestoreModal = false;
        $this->reset(['selectedVehicleId', 'selectedVehicleDisabled', 'selectedVehicleName', 'vehicle', 'original_vehicle', 'create_vehicle']);
        $this->resetValidation();
    }

    public function openCreateModal()
    {
        $this->reset(['create_vehicle']);
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    /**
     * Sanitize and uppercase vehicle
     * Removes HTML tags, trims whitespace, and converts to uppercase
     * 
     * @param string $vehicle
     * @return string
     */
    private function sanitizeAndUppercaseVehicle($vehicle)
    {
        if (empty($vehicle)) {
            return '';
        }

        // Remove HTML tags and trim whitespace
        $vehicle = strip_tags(trim($vehicle));
        
        // Decode HTML entities (e.g., &amp; becomes &, &#39; becomes ')
        $vehicle = html_entity_decode($vehicle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove any null bytes and other control characters (except newlines/spaces)
        $vehicle = preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/u', '', $vehicle);
        
        // Convert dashes to spaces for backward compatibility
        $vehicle = str_replace('-', ' ', $vehicle);
        
        // Normalize whitespace (replace multiple spaces with single space)
        $vehicle = preg_replace('/\s+/', ' ', $vehicle);
        
        // Trim again after normalization
        $vehicle = trim($vehicle);
        
        // Convert to uppercase
        return mb_strtoupper($vehicle, 'UTF-8');
    }

    public function createVehicle()
    {
        // Authorization check
        if (Auth::user()->user_type < 2) {
            abort(403, 'Unauthorized action.');
        }

        // Sanitize and uppercase input BEFORE validation
        $vehicle = $this->sanitizeAndUppercaseVehicle($this->create_vehicle);
        
        // Basic validation - just ensure it's not empty after sanitization
        if (empty(trim($vehicle))) {
            $this->addError('create_vehicle', 'Vehicle is required.');
            return;
        }

        // Update the property with sanitized value for validation
        $this->create_vehicle = $vehicle;

        // Validate with sanitized value
        $this->validate([
            'create_vehicle' => ['required', 'string', 'max:20', 'unique:vehicles,vehicle'],
        ], [
            'create_vehicle.required' => 'Vehicle is required.',
            'create_vehicle.max' => 'Vehicle must not exceed 20 characters.',
            'create_vehicle.unique' => 'This vehicle already exists.',
        ], [
            'create_vehicle' => 'Vehicle',
        ]);

        // Create vehicle
        $vehicle = Vehicle::create([
            'vehicle' => $vehicle,
            'disabled' => false,
        ]);

        Cache::forget('vehicles_all');

        // Log the create action
        Logger::create(
            Vehicle::class,
            $vehicle->id,
            "Created \"{$vehicle}\"",
            $vehicle->only(['vehicle', 'disabled'])
        );

        $this->showCreateModal = false;
        $this->reset(['create_vehicle']);
        $this->dispatch('toast', message: "Vehicle {$vehicle} has been created.", type: 'success');
        $this->resetPage();
    }

    public function render()
    {
        $query = $this->showDeleted 
            ? Vehicle::onlyTrashed()
            : Vehicle::whereNull('deleted_at');
        
        $vehicles = $query->when($this->search, function ($query) {
                $searchTerm = $this->search;
                
                // Sanitize search term to prevent SQL injection
                $searchTerm = trim($searchTerm);
                $searchTerm = preg_replace('/[%_]/', '', $searchTerm); // Remove LIKE wildcards for safety
                
                if (empty($searchTerm)) {
                    return;
                }
                
                // Escape special characters for LIKE
                $escapedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                
                // Search vehicle
                $query->where('vehicle', 'like', '%' . $escapedSearchTerm . '%');
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
                    $this->sortColumns = ['vehicle' => 'asc'];
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
                $query->orderBy('vehicle', 'asc');
            })
            ->when($this->showDeleted, function ($query) {
                $query->orderBy('deleted_at', 'desc');
            })
            ->paginate(10);

        $filtersActive = $this->appliedStatus !== null || !empty($this->appliedCreatedFrom) || !empty($this->appliedCreatedTo);

        return view('livewire.super-admin.vehicles', [
            'vehicles' => $vehicles,
            'filtersActive' => $filtersActive,
            'availableStatuses' => $this->availableStatuses,
        ]);
    }

    public function getExportData()
    {
        $query = $this->showDeleted 
            ? Vehicle::onlyTrashed()
            : Vehicle::whereNull('deleted_at');
        
        return $query->when($this->search, function ($query) {
                $searchTerm = trim($this->search);
                $searchTerm = preg_replace('/[%_]/', '', $searchTerm);
                if (empty($searchTerm)) {
                    return $query;
                }
                $escapedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                $query->where('vehicle', 'like', '%' . $escapedSearchTerm . '%');
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
                $query->orderBy('vehicle', 'asc');
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
        $filename = 'vehicles_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'Photo; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['Vehicle', 'Status', 'Created Date']);
            
            foreach ($data as $vehicle) {
                $status = $vehicle->disabled ? 'Disabled' : 'Enabled';
                fputcsv($file, [
                    $vehicle->vehicle,
                    $status,
                    $vehicle->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function toggleDeletedView()
    {
        $this->showDeleted = !$this->showDeleted;
        
        if ($this->showDeleted) {
            // Entering restore mode: Store current values only if not already stored, then clear date filters
            if ($this->previousAppliedCreatedFrom === null && $this->previousAppliedCreatedTo === null) {
                $this->previousFilterCreatedFrom = $this->filterCreatedFrom;
                $this->previousFilterCreatedTo = $this->filterCreatedTo;
                $this->previousAppliedCreatedFrom = $this->appliedCreatedFrom;
                $this->previousAppliedCreatedTo = $this->appliedCreatedTo;
            }
            
            $this->filterCreatedFrom = '';
            $this->filterCreatedTo = '';
            $this->appliedCreatedFrom = '';
            $this->appliedCreatedTo = '';
        } else {
            // Exiting restore mode: Always restore previous values, then reset stored values
            $this->filterCreatedFrom = $this->previousFilterCreatedFrom ?? '';
            $this->filterCreatedTo = $this->previousFilterCreatedTo ?? '';
            $this->appliedCreatedFrom = $this->previousAppliedCreatedFrom ?? '';
            $this->appliedCreatedTo = $this->previousAppliedCreatedTo ?? '';
            
            // Reset stored values for next time
            $this->previousFilterCreatedFrom = null;
            $this->previousFilterCreatedTo = null;
            $this->previousAppliedCreatedFrom = null;
            $this->previousAppliedCreatedTo = null;
        }
        
        $this->resetPage();
    }

    public function openRestoreModal($vehicleId)
    {
        $vehicle = Vehicle::onlyTrashed()->findOrFail($vehicleId);
        $this->selectedVehicleId = $vehicleId;
        $this->selectedVehicleName = $vehicle->vehicle;
        $this->showRestoreModal = true;
    }

    public function restoreVehicle()
    {
        // Prevent multiple submissions
        if ($this->isRestoring) {
            return;
        }

        $this->isRestoring = true;

        try {
        // Authorization check
        if (Auth::user()->user_type < 2) {
            abort(403, 'Unauthorized action.');
        }

        if (!$this->selectedVehicleId) {
            return;
        }

        // Atomic restore: Only restore if currently deleted to prevent race conditions
        // Do the atomic update first, then load the model only if successful
        $restored = Vehicle::onlyTrashed()
            ->where('id', $this->selectedVehicleId)
            ->update(['deleted_at' => null]);
        
        if ($restored === 0) {
            // Vehicle was already restored or doesn't exist
            $this->showRestoreModal = false;
            $this->reset(['selectedVehicleId', 'selectedVehicleName']);
            $this->dispatch('toast', message: 'This vehicle was already restored or does not exist. Please refresh the page.', type: 'error');
            $this->resetPage();
            return;
        }
        
        // Now load the restored vehicle
        $vehicle = Vehicle::findOrFail($this->selectedVehicleId);
        
        // Log the restore action
        Logger::restore(
            Vehicle::class,
            $vehicle->id,
            "Restored vehicle {$vehicle->vehicle}"
        );
        
        Cache::forget('vehicles_all');

        $this->showRestoreModal = false;
        $this->reset(['selectedVehicleId', 'selectedVehicleName']);
        $this->resetPage();
        $this->dispatch('toast', message: "{$vehicle->vehicle} has been restored.", type: 'success');
        } finally {
            $this->isRestoring = false;
        }
    }

    public function openPrintView()
    {
        if ($this->showDeleted) {
            return;
        }
        
        $data = $this->getExportData();
        $exportData = $data->map(function($vehicle) {
            return [
                'vehicle' => $vehicle->vehicle,
                'disabled' => $vehicle->disabled,
                'created_at' => $vehicle->created_at->toIso8601String(),
            ];
        })->toArray();
        
        $filters = [
            'search' => $this->search,
            'status' => $this->appliedStatus,
            'created_from' => $this->appliedCreatedFrom,
            'created_to' => $this->appliedCreatedTo,
        ];
        
        $sorting = $this->sortColumns ?? ['vehicle' => 'asc'];
        
        $token = Str::random(32);
        Session::put("export_data_{$token}", $exportData);
        Session::put("export_filters_{$token}", $filters);
        Session::put("export_sorting_{$token}", $sorting);
        Session::put("export_data_{$token}_expires", now()->addMinutes(10));
        
        $printUrl = route('superadmin.print.vehicles', ['token' => $token]);
        
        $this->dispatch('open-print-window', ['url' => $printUrl]);
    }
}
