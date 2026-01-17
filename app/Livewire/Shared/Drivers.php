<?php

namespace App\Livewire\Shared;

use App\Models\Driver;
use App\Services\Logger;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Shared Drivers Component
 * 
 * This component can be used by Admin, SuperAdmin, and User/Data
 * with role-based configuration via the $config property.
 */
class Drivers extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Role-based configuration
    public $config = [
        'role' => 'admin', // 'admin', 'superadmin', or 'user'
        'showRestore' => false, // Show restore functionality
        'printRoute' => 'admin.print.drivers', // Route name for print functionality
        'minUserType' => 1, // Minimum user_type required (1 = admin, 2 = superadmin)
    ];

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
    
    // Store previous date filter values when entering restore mode
    private $previousFilterCreatedFrom = null;
    private $previousFilterCreatedTo = null;
    private $previousAppliedCreatedFrom = null;
    private $previousAppliedCreatedTo = null;
    
    public $availableStatuses = [
        0 => 'Enabled',
        1 => 'Disabled',
    ];
    
    // Restore functionality (only for superadmin)
    public $showDeleted = false;
    public $selectedDriverId;
    public $selectedDriverName = '';
    public $showRestoreModal = false;
    public $isRestoring = false;

    protected $queryString = ['search'];

    protected $listeners = [
        'driver-created' => 'handleDriverCreated',
        'driver-updated' => 'handleDriverUpdated',
        'driver-deleted' => 'handleDriverDeleted',
        'driver-status-toggled' => 'handleDriverStatusToggled',
    ];

    public function mount($config = [])
    {
        // Merge provided config with defaults
        $this->config = array_merge($this->config, $config);
    }

    public function handleDriverCreated()
    {
        $this->resetPage();
    }

    public function handleDriverUpdated()
    {
        $this->resetPage();
    }

    public function handleDriverDeleted()
    {
        $this->resetPage();
    }

    public function handleDriverStatusToggled()
    {
        $this->resetPage();
    }
    
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

    public function openCreateModal()
    {
        // Dispatch event to the DriverCreate component
        $this->dispatch('openCreateModal');
    }

    public function openEditModal($driverId)
    {
        // Dispatch event to the DriverEdit component
        $this->dispatch('openEditModal', $driverId);
    }

    public function openDisableModal($driverId)
    {
        // Dispatch event to the DriverDisable component
        $this->dispatch('openDisableModal', $driverId);
    }

    public function openDeleteModal($driverId)
    {
        // Dispatch event to the DriverDelete component
        $this->dispatch('openDeleteModal', $driverId);
    }

    public function closeModal()
    {
        // No-op - modals are handled by child components
    }

    public function toggleDeletedView()
    {
        if (!$this->config['showRestore']) {
            return;
        }

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

    public function openRestoreModal($driverId)
    {
        if (!$this->config['showRestore']) {
            return;
        }

        $driver = Driver::onlyTrashed()->findOrFail($driverId);
        $this->selectedDriverId = $driverId;
        $this->selectedDriverName = $this->getDriverFullName($driver);
        $this->showRestoreModal = true;
    }

    public function restoreDriver()
    {
        if (!$this->config['showRestore']) {
            return;
        }

        // Prevent multiple submissions
        if ($this->isRestoring) {
            return;
        }

        // Authorization check
        if (Auth::user()->user_type < $this->config['minUserType']) {
            abort(403, 'Unauthorized action.');
        }

        $this->isRestoring = true;

        try {
            if (!$this->selectedDriverId) {
                return;
            }

            // Atomic restore: Only restore if currently deleted to prevent race conditions
            $restored = Driver::onlyTrashed()
                ->where('id', $this->selectedDriverId)
                ->update(['deleted_at' => null]);
            
            if ($restored === 0) {
                // Driver was already restored or doesn't exist
                $this->showRestoreModal = false;
                $this->reset(['selectedDriverId', 'selectedDriverName']);
                $this->dispatch('toast', message: 'This driver was already restored or does not exist. Please refresh the page.', type: 'error');
                $this->resetPage();
                return;
            }
            
            // Now load the restored driver
            $driver = Driver::findOrFail($this->selectedDriverId);
            
            // Log the restore action
            Logger::restore(
                Driver::class,
                $driver->id,
                "Restored driver {$this->getDriverFullName($driver)}"
            );
            
            Cache::forget('drivers_all');

            $this->showRestoreModal = false;
            $this->reset(['selectedDriverId', 'selectedDriverName']);
            $this->resetPage();
            $this->dispatch('toast', message: "{$this->getDriverFullName($driver)} has been restored.", type: 'success');
        } finally {
            $this->isRestoring = false;
        }
    }

    public function openPrintView()
    {
        if ($this->showDeleted || !isset($this->config['printRoute'])) {
            return;
        }
        
        $data = $this->getExportData();
        $exportData = $data->map(function($driver) {
            return [
                'first_name' => $driver->first_name,
                'middle_name' => $driver->middle_name,
                'last_name' => $driver->last_name,
                'disabled' => $driver->disabled,
                'created_at' => $driver->created_at->toIso8601String(),
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
        
        $printUrl = route($this->config['printRoute'], ['token' => $token]);
        
        $this->dispatch('open-print-window', ['url' => $printUrl]);
    }

    public function exportCSV()
    {
        if ($this->showDeleted) {
            return;
        }
        
        $data = $this->getExportData();
        $filename = 'drivers_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'Photo; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['First Name', 'Middle Name', 'Last Name', 'Status', 'Created Date']);
            
            foreach ($data as $driver) {
                $status = $driver->disabled ? 'Disabled' : 'Enabled';
                fputcsv($file, [
                    $driver->first_name,
                    $driver->middle_name ?? '',
                    $driver->last_name,
                    $status,
                    $driver->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function getExportData()
    {
        $query = $this->config['showRestore'] && $this->showDeleted 
            ? Driver::onlyTrashed()
            : Driver::whereNull('deleted_at');
        
        return $query->when($this->search, function ($query) {
                $searchTerm = trim($this->search);
                $searchTerm = preg_replace('/[%_]/', '', $searchTerm);
                if (empty($searchTerm)) {
                    return $query;
                }
                $escapedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                $query->where(function ($q) use ($escapedSearchTerm) {
                    $q->where('first_name', 'like', '%' . $escapedSearchTerm . '%')
                      ->orWhere('middle_name', 'like', '%' . $escapedSearchTerm . '%')
                      ->orWhere('last_name', 'like', '%' . $escapedSearchTerm . '%')
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%'])
                      ->orWhereRaw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%']);
                });
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
                $query->orderBy('first_name', 'asc');
            })
            ->when($this->showDeleted, function ($query) {
                $query->orderBy('deleted_at', 'desc');
            })
            ->get();
    }

    /**
     * Helper method to get driver full name
     */
    protected function getDriverFullName($driver)
    {
        $name = $driver->first_name;
        if (!empty($driver->middle_name)) {
            $name .= ' ' . $driver->middle_name;
        }
        $name .= ' ' . $driver->last_name;
        return $name;
    }

    public function render()
    {
        $query = $this->config['showRestore'] && $this->showDeleted 
            ? Driver::onlyTrashed()
            : Driver::whereNull('deleted_at');
        
        $drivers = $query->when($this->search, function ($query) {
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
            ->when(empty($this->sortColumns) && !$this->showDeleted, function($query) {
                // Default sort if no sorts are set
                $query->orderBy('first_name', 'asc');
            })
            ->when($this->showDeleted, function ($query) {
                $query->orderBy('deleted_at', 'desc');
            })
            ->paginate(10);

        $filtersActive = $this->appliedStatus !== null || !empty($this->appliedCreatedFrom) || !empty($this->appliedCreatedTo);

        return view('livewire.shared.drivers', [
            'drivers' => $drivers,
            'filtersActive' => $filtersActive,
            'availableStatuses' => $this->availableStatuses,
        ]);
    }
}
