<?php

namespace App\Livewire\Shared;

use App\Models\Location;
use App\Models\Setting;
use App\Services\Logger;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Shared Locations Component
 * 
 * This component can be used by Admin and SuperAdmin
 * with role-based configuration via the $config property.
 */
class Locations extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Role-based configuration
    public $config = [
        'role' => 'admin', // 'admin', 'superadmin', or 'user'
        'showRestore' => false, // Show restore functionality
        'printRoute' => 'admin.print.locations', // Route name for print functionality
        'minUserType' => 1, // Minimum user_type required (1 = admin, 2 = superadmin)
        'viewPath' => 'livewire.shared.locations', // View path for rendering
    ];

    public $search = '';
    public $showFilters = false;
    
    // Sorting properties
    public $sortColumns = ['location_name' => 'asc']; // Default sort by location_name ascending
    
    // Filter properties
    public $filterStatus = null; // null = All Locations, 0 = Enabled, 1 = Disabled
    public $filterCreatedFrom = '';
    public $filterCreatedTo = '';
    
    // Applied filters
    public $appliedStatus = null; // null = All Locations, 0 = Enabled, 1 = Disabled
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
    
    // Restore functionality moved to Shared\Locations\Restore component
    public $showDeleted = false;

    protected $queryString = ['search'];

    protected $listeners = [
        'location-created' => 'handleLocationCreated',
        'location-updated' => 'handleLocationUpdated',
        'location-deleted' => 'handleLocationDeleted',
        'location-status-toggled' => 'handleLocationStatusToggled',
    ];

    public function mount($config = [])
    {
        // Auto-detect user type if config not provided
        $user = Auth::user();
        $userType = $user->user_type ?? 1;
        $isSuperGuard = ($user->user_type === 0 && $user->super_guard) ?? false;
        $isSuperAdmin = $userType === 2;
        
        // Default config based on user type
        if ($isSuperGuard) {
            $defaultConfig = [
                'role' => 'user',
                'showRestore' => false,
                'printRoute' => 'user.print.locations',
                'minUserType' => 0,
                'viewPath' => 'livewire.shared.locations',
            ];
        } else {
            $defaultConfig = [
                'role' => $isSuperAdmin ? 'superadmin' : 'admin',
                'showRestore' => $isSuperAdmin,
                'printRoute' => $isSuperAdmin ? 'superadmin.print.locations' : 'admin.print.locations',
                'minUserType' => $isSuperAdmin ? 2 : 1,
                'viewPath' => 'livewire.shared.locations',
            ];
        }
        
        // Merge provided config with defaults
        $this->config = array_merge($defaultConfig, $config);
    }

    public function handleLocationCreated()
    {
        $this->resetPage();
    }

    public function handleLocationUpdated()
    {
        Cache::forget('locations_all');
        $this->resetPage();

        // Force component to re-render with fresh data
        $this->dispatch('$refresh');
    }

    public function handleLocationDeleted()
    {
        $this->resetPage();
    }

    public function handleLocationStatusToggled()
    {
        $this->resetPage();
    }
    
    // Ensure filterStatus is properly typed when updated
    public function updatedFilterStatus($value)
    {
        // Handle null, empty string, or numeric values (0, 1)
        // null/empty = All Locations, 0 = Enabled, 1 = Disabled
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
        
        // If no sorts remain, default to location_name ascending
        if (empty($this->sortColumns)) {
            $this->sortColumns = ['location_name' => 'asc'];
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
        // Dispatch event to the LocationCreate component
        $this->dispatch('openCreateModal');
    }

    public function openEditModal($locationId)
    {
        // Dispatch event to the LocationEdit component
        $this->dispatch('openEditModal', $locationId);
    }

    public function openDisableModal($locationId)
    {
        // Dispatch event to the LocationDisable component
        $this->dispatch('openDisableModal', $locationId);
    }

    public function openDeleteModal($locationId)
    {
        // Dispatch event to the LocationDelete component
        $this->dispatch('openDeleteModal', $locationId);
    }

    public function closeModal()
    {
        // No-op - modals are handled by child components
    }

    /**
     * Get default location logo path from settings table
     * Falls back to hardcoded value if setting doesn't exist
     * 
     * @return string
     */
    public function getDefaultLogoPath()
    {
        $setting = Setting::where('setting_name', 'default_location_logo')->first();
        
        if ($setting && !empty($setting->value)) {
            return $setting->value;
        }
        
        // Fallback to default (shouldn't happen if seeded properly)
        return 'images/logo/BGC.png';
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

    public function openRestoreModal($locationId)
    {
        if (!$this->config['showRestore']) {
            return;
        }

        // Dispatch event to the Restore component
        $this->dispatch('openRestoreModal', $locationId);
    }

    #[On('location-restored')]
    public function handleLocationRestored()
    {
        Cache::forget('locations_all');
        $this->resetPage();
    }

    public function openPrintView()
    {
        if ($this->showDeleted || !isset($this->config['printRoute'])) {
            return;
        }
        
        $data = $this->getExportData();
        $exportData = $data->map(function($location) {
            return [
                'location_name' => $location->location_name,
                'disabled' => $location->disabled,
                'created_at' => $location->created_at->toIso8601String(),
            ];
        })->toArray();
        
        $filters = [
            'search' => $this->search,
            'status' => $this->appliedStatus,
            'created_from' => $this->appliedCreatedFrom,
            'created_to' => $this->appliedCreatedTo,
        ];
        
        $sorting = $this->sortColumns ?? ['location_name' => 'asc'];
        
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
        $filename = 'locations_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'Photo; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['Location Name', 'Status', 'Created Date']);
            
            foreach ($data as $location) {
                $status = $location->disabled ? 'Disabled' : 'Enabled';
                fputcsv($file, [
                    $location->location_name,
                    $status,
                    $location->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function getExportData()
    {
        $query = $this->config['showRestore'] && $this->showDeleted 
            ? Location::onlyTrashed()->with('Photo')
            : Location::with('Photo')->whereNull('deleted_at');
        
        return $query->when($this->search, function ($query) {
                $searchTerm = trim($this->search);
                $searchTerm = preg_replace('/[%_]/', '', $searchTerm);
                if (empty($searchTerm)) {
                    return $query;
                }
                $escapedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                $query->where('location_name', 'like', '%' . $escapedSearchTerm . '%');
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
                $query->orderBy('location_name', 'asc');
            })
            ->when($this->showDeleted, function ($query) {
                $query->orderBy('deleted_at', 'desc');
            })
            ->get();
    }

    public function render()
    {
        $query = $this->config['showRestore'] && $this->showDeleted 
            ? Location::onlyTrashed()->with('Photo')
            : Location::with('Photo')->whereNull('deleted_at');
        
        $locations = $query->when($this->search, function ($query) {
                $searchTerm = $this->search;
                
                // Sanitize search term to prevent SQL injection
                $searchTerm = trim($searchTerm);
                $searchTerm = preg_replace('/[%_]/', '', $searchTerm); // Remove LIKE wildcards for safety
                
                if (empty($searchTerm)) {
                    return;
                }
                
                // Escape special characters for LIKE
                $escapedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                
                $query->where('location_name', 'like', '%' . $escapedSearchTerm . '%');
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
                    $this->sortColumns = ['location_name' => 'asc'];
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
                $query->orderBy('location_name', 'asc');
            })
            ->when($this->showDeleted, function ($query) {
                $query->orderBy('deleted_at', 'desc');
            })
            ->paginate(10);

        $filtersActive = $this->appliedStatus !== null || !empty($this->appliedCreatedFrom) || !empty($this->appliedCreatedTo);

        $defaultLogoPath = $this->getDefaultLogoPath();

        return view($this->config['viewPath'] ?? 'livewire.shared.locations', [
            'locations' => $locations,
            'filtersActive' => $filtersActive,
            'availableStatuses' => $this->availableStatuses,
            'defaultLogoPath' => $defaultLogoPath,
        ]);
    }
}
