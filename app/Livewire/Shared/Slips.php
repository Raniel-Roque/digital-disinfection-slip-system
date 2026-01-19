<?php

namespace App\Livewire\Shared;

use Livewire\Component;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use App\Models\DisinfectionSlip as DisinfectionSlipModel;
use App\Models\Photo;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\Driver;
use App\Models\User;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\Logger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class Slips extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $search = '';
    public $showFilters = false;
    
    // Sorting properties
    public $sortBy = 'slip_id'; // Default sort by slip_id
    public $sortDirection = 'desc'; // Default descending
    
    // Filter fields
    public $filterStatus = null; // null = All Statuses, 0 = Pending, 1 = Disinfecting, 2 = In-Transit, 3 = Completed
    
    // Ensure filterStatus is properly typed when updated
    public function updatedFilterStatus($value)
    {
        // Handle null, empty string, or numeric values (0, 1, 2, 3, 4 matching backend)
        // null/empty = All Statuses, 0 = Pending, 1 = Disinfecting, 2 = In-Transit, 3 = Completed, 4 = Incomplete
        // The select will send values as strings, so we convert to int
        if ($value === null || $value === '' || $value === false) {
            $this->filterStatus = null;
        } elseif (is_numeric($value)) {
            $intValue = (int)$value;
            if ($intValue >= 0 && $intValue <= 4) {
                // Store as integer (0, 1, 2, 3, or 4)
                $this->filterStatus = $intValue;
            } else {
                $this->filterStatus = null;
            }
        } else {
            $this->filterStatus = null;
        }
    }
    public $filterOrigin = [];
    public $filterDestination = [];
    public $filterDriver = [];
    public $filterVehicle = [];
    public $filterHatcheryGuard = [];
    public $filterReceivedGuard = [];
    public $filterCreatedFrom = '';
    public $filterCreatedTo = '';
    
    // Search properties for filter dropdowns
    public $searchFilterVehicle = '';
    public $searchFilterDriver = '';
    public $searchFilterOrigin = '';
    public $searchFilterDestination = '';
    public $searchFilterHatcheryGuard = '';
    public $searchFilterReceivedGuard = '';
    
    // Applied filters (stored separately)
    public $appliedStatus = null; // null = All Statuses, 0 = Pending, 1 = Disinfecting, 2 = In-Transit, 3 = Completed
    public $appliedOrigin = [];
    public $appliedDestination = [];
    public $appliedDriver = [];
    public $appliedVehicle = [];
    public $appliedHatcheryGuard = [];
    public $appliedReceivedGuard = [];
    public $appliedCreatedFrom = null;
    public $appliedCreatedTo = null;
    
    public $filtersActive = false;
    public $excludeDeletedItems = true; // Default: exclude slips with deleted related items
    
    // Store previous date filter values when entering restore mode
    private $previousFilterCreatedFrom = null;
    private $previousFilterCreatedTo = null;
    private $previousAppliedCreatedFrom = null;
    private $previousAppliedCreatedTo = null;
    
    public $availableStatuses = [
        0 => 'Pending',
        1 => 'Disinfecting',
        2 => 'In-Transit',
        3 => 'Completed',
        4 => 'Incomplete',
    ];

    // Details Modal - kept for viewing slip details
    public $showDetailsModal = false;
    public $showAttachmentModal = false;
    public $showCreateModal = false;
    public $showCancelEditConfirmation = false;
    public $showDeleteConfirmation = false;
    public $showRemoveAttachmentConfirmation = false;
    public $selectedSlip = null;
    public $currentAttachmentIndex = 0;
    public $attachmentToDelete = null;

    // Restore functionality moved to Shared\Slips\Restore component
    public $showDeleted = false; // Toggle to show deleted items


    private $cachedFilterGuards = null;
    private $cachedFilterGuardsCollection = null;

    // Config properties
    public $role = 'superadmin';
    public $showRestore = true;
    public $viewPath = 'livewire.shared.slips';
    public $printRoutePrefix = 'superadmin';
    public $minUserType = 2;

    public function mount($config = [])
    {
        // Auto-detect user type if config not provided
        $userType = Auth::user()->user_type ?? 1;
        $isSuperAdmin = $userType === 2;
        
        // Apply config or use auto-detected values
        $this->role = $config['role'] ?? ($isSuperAdmin ? 'superadmin' : 'admin');
        $this->showRestore = $config['showRestore'] ?? $isSuperAdmin;
        $this->viewPath = $config['viewPath'] ?? 'livewire.shared.slips';
        $this->printRoutePrefix = $config['printRoutePrefix'] ?? ($isSuperAdmin ? 'superadmin' : 'admin');
        $this->minUserType = $config['minUserType'] ?? ($isSuperAdmin ? 2 : 1);
        
        // Initialize array filters
        $this->filterOrigin = [];
        $this->filterDestination = [];
        $this->filterDriver = [];
        $this->filterVehicle = [];
        $this->filterHatcheryGuard = [];
        $this->filterReceivedGuard = [];
        $this->appliedOrigin = [];
        $this->appliedDestination = [];
        $this->appliedDriver = [];
        $this->appliedVehicle = [];
        $this->appliedHatcheryGuard = [];
        $this->appliedReceivedGuard = [];
        
        // Set default filter to today's date
        $today = now()->format('Y-m-d');
        $this->filterCreatedFrom = $today;
        $this->filterCreatedTo = $today;
        $this->appliedCreatedFrom = $today;
        $this->appliedCreatedTo = $today;
        $this->filtersActive = true;
    }


    /**
     * Handle slip creation completion
     */
    #[On('slip-created')]
    public function handleSlipCreated()
    {
        $this->resetPage();
    }

    /**
     * Handle slip updates
     */
    /**
     * Handle slip deletion
     */
    /**
     * Handle slip restoration
     */
    /**
     * Prevent polling from running when any modal is open
     * This prevents the selected slip data from being overwritten
     */
    #[On('polling')]
    public function polling()
    {
        // If any modal is open, skip polling
        if ($this->showFilters || $this->showDetailsModal ||
            $this->showRemoveAttachmentConfirmation || $this->showAttachmentModal) {
            return;
        }

        // If a slip is selected, reload it with trashed relations (including if the slip itself is deleted)
        // Optimize relationship loading by only selecting needed fields
        if ($this->selectedSlip) {
            $this->selectedSlip = DisinfectionSlipModel::withTrashed()->with([
                'vehicle' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
                'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
                'reason:id,reason_text,is_disabled',
                'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
                'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); }
            ])->find($this->selectedSlip->id);
        }
    }
    
    // Helper methods to get cached collections
    private function getCachedLocations()
    {
        // Shorter cache (5 min) since comment says "ensure disabled status is current"
        // Only cache id and location_name to reduce memory usage with large datasets
        return Cache::remember('locations_all', 300, function() {
            return Location::withTrashed()
                ->select('id', 'location_name', 'disabled', 'deleted_at')
                ->orderBy('location_name')
                ->get();
        });
    }
    
    private function getCachedDrivers()
    {
        // Only cache id and name fields to reduce memory usage with large datasets
        return Cache::remember('drivers_all', 300, function() {
            return Driver::withTrashed()
                ->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')
                ->orderBy('first_name')
                ->get();
        });
    }
    
    private function getCachedVehicles()
    {
        // Only cache id and vehicle to reduce memory usage with large datasets
        return Cache::remember('vehicles_all', 300, function() {
            return Vehicle::withTrashed()
                ->select('id', 'vehicle', 'disabled', 'deleted_at')
                ->orderBy('vehicle')
                ->get();
        });
    }
    
    private function getCachedGuards()
    {
        // Only cache id and name fields, return as array to reduce memory usage
        return Cache::remember('guards_all', 300, function() {
            return User::where('user_type', '=', 0)
                ->where('disabled', '=', false)
                ->select('id', 'first_name', 'middle_name', 'last_name', 'username')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get()
                ->mapWithKeys(function ($user) {
                    $name = trim("{$user->first_name} {$user->middle_name} {$user->last_name}");
                    return [$user->id => "{$name} @{$user->username}"];
                });
        });
    }
    
    // Removed getCachedReasons() - now using direct database pagination in getReasonsProperty()
    
    // Get guards for filtering (includes disabled guards) - cached User collection
    // Only cache id and name fields to reduce memory usage
    private function getFilterGuardsCollection()
    {
        if ($this->cachedFilterGuardsCollection === null) {
            $this->cachedFilterGuardsCollection = User::where('user_type', '=', 0)
                ->select('id', 'first_name', 'middle_name', 'last_name', 'disabled')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();
        }
        return $this->cachedFilterGuardsCollection;
    }
    
    // Get guards as mapped collection (id => name) for dropdowns
    private function getFilterGuards()
    {
        if ($this->cachedFilterGuards === null) {
            $users = $this->getFilterGuardsCollection();
            $this->cachedFilterGuards = $users->mapWithKeys(function ($user) {
                $name = trim("{$user->first_name} {$user->middle_name} {$user->last_name}");
                return [$user->id => "{$name} @{$user->username}"];
            });
        }
        return $this->cachedFilterGuards;
    }

    // Computed property for locations
    public function getLocationsProperty()
    {
        // Only load locations that are actually used in applied filters
        $locationIds = array_merge(
            $this->filterOrigin ?? [],
            $this->filterDestination ?? []
        );
        
        if (empty($locationIds)) {
            return collect();
        }
        
        // Only fetch the locations we actually need
        return Location::withTrashed()
            ->whereIn('id', $locationIds)
            ->select('id', 'location_name', 'disabled', 'deleted_at')
            ->get()
            ->keyBy('id');
    }

    // Computed property for drivers - lazy load only what's needed
    public function getDriversProperty()
    {
        // Only load drivers that are actually used in applied filters
        $driverIds = $this->filterDriver ?? [];
        
        if (empty($driverIds)) {
            return collect();
        }
        
        // Only fetch the drivers we actually need
        return Driver::withTrashed()
            ->whereIn('id', $driverIds)
            ->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')
            ->get()
            ->keyBy('id');
    }

    // Computed property for vehicles - lazy load only what's needed
    public function getVehiclesProperty()
    {
        // Only load vehicles that are actually used in applied filters
        $vehicleIds = $this->filterVehicle ?? [];
        
        if (empty($vehicleIds)) {
            return collect();
        }
        
        // Only fetch the vehicles we actually need
        return Vehicle::withTrashed()
            ->whereIn('id', $vehicleIds)
            ->select('id', 'vehicle', 'disabled', 'deleted_at')
            ->get()
            ->keyBy('id');
    }
    
    // Helper method to ensure selected values are always included in filtered options
    private function ensureSelectedInOptions($options, $selectedValues, $allOptions = null)
    {
        if (empty($selectedValues)) {
            return $options;
        }
        
        // If allOptions not provided, use options as the source
        if ($allOptions === null) {
            $allOptions = $options;
        }
        
        $selectedArray = is_array($selectedValues) ? $selectedValues : [$selectedValues];
        $allOptionsArray = is_array($allOptions) ? $allOptions : $allOptions->toArray();
        $optionsArray = is_array($options) ? $options : $options->toArray();
        
        // Add any selected values that aren't already in the filtered options
        foreach ($selectedArray as $selectedId) {
            if (isset($allOptionsArray[$selectedId]) && !isset($optionsArray[$selectedId])) {
                $optionsArray[$selectedId] = $allOptionsArray[$selectedId];
            }
        }
        
        return is_array($options) ? $optionsArray : collect($optionsArray);
    }
    
    // NOTE: Filter options removed - now using paginated dropdowns with getPaginatedX methods
    // Old getFilterVehicleOptionsProperty, getFilterDriverOptionsProperty, etc. removed

    // Computed property for guards (users) - lazy load only what's needed
    public function getGuardsProperty()
    {
        // Only load guards that are actually used in applied filters
        $guardIds = array_merge(
            $this->appliedHatcheryGuard ?? [],
            $this->appliedReceivedGuard ?? []
        );
        
        if (empty($guardIds)) {
            return collect();
        }
        
        // Only fetch the guards we actually need
        return User::withTrashed()
            ->whereIn('id', $guardIds)
            ->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'deleted_at')
            ->get()
            ->keyBy('id');
    }

    // Computed property for available origins (excludes selected destination)
    public function getAvailableOriginsProperty()
    {
        $locations = $this->getCachedLocations()->whereNull('deleted_at');
        
        if ($this->destination_id) {
            return $locations->where('id', '!=', $this->destination_id)
                ->pluck('location_name', 'id')
                ->toArray();
        }
        
        return $locations->pluck('location_name', 'id')->toArray();
    }

    // Computed property for available destinations (excludes selected origin)
    public function getAvailableDestinationsProperty()
    {
        $locations = $this->getCachedLocations()->whereNull('deleted_at');
        
        if ($this->location_id) {
            return $locations->where('id', '!=', $this->location_id)
                ->pluck('location_name', 'id')
                ->toArray();
        }
        
        return $locations->pluck('location_name', 'id')->toArray();
    }
    
    // Computed properties for create modal filtered options
    // NOTE: Create modal options removed - now using paginated dropdowns with getPaginatedX methods
    
    // Computed properties for details modal filtered options
    public function getDetailsVehicleOptionsProperty()
    {
        $vehicles = $this->getCachedVehicles()->whereNull('deleted_at');
        $allOptions = $vehicles->pluck('vehicle', 'id');
        $options = $allOptions;
        
        if (!empty($this->searchDetailsVehicle)) {
            $searchTerm = strtolower($this->searchDetailsVehicle);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected value is always included
            $options = $this->ensureSelectedInOptions($options, $this->vehicle_id, $allOptions);
        }
        
        return $options->toArray();
    }
    
    public function getDetailsLocationOptionsProperty()
    {
        $locations = $this->getCachedLocations()->whereNull('deleted_at');
        $allOptions = $locations->pluck('location_name', 'id');
        $options = $allOptions;
        
        if (!empty($this->searchDetailsDestination)) {
            $searchTerm = strtolower($this->searchDetailsDestination);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected value is always included
            $options = $this->ensureSelectedInOptions($options, $this->destination_id, $allOptions);
        }
        
        return $options->toArray();
    }
    
    public function getDetailsDriverOptionsProperty()
    {
        $drivers = $this->getCachedDrivers()->whereNull('deleted_at');
        $allOptions = $drivers->pluck('full_name', 'id');
        $options = $allOptions;
        
        if (!empty($this->searchDetailsDriver)) {
            $searchTerm = strtolower($this->searchDetailsDriver);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected value is always included
            $options = $this->ensureSelectedInOptions($options, $this->driver_id, $allOptions);
        }
        
        return $options->toArray();
    }
    
    // NOTE: Edit modal options removed - now using paginated dropdowns with getPaginatedX methods

    // Paginated data fetching methods for searchable dropdowns
    #[Renderless]
    public function getPaginatedVehicles($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = Vehicle::query()
            ->whereNull('deleted_at')
            ->where('disabled', false)
            ->select(['id', 'vehicle']);

        // Apply search filter
        if (!empty($search)) {
            $query->where('vehicle', 'like', '%' . $search . '%');
        }

        // Include specific IDs (for selected items)
        if (!empty($includeIds)) {
            $includedItems = Vehicle::whereIn('id', $includeIds)
                ->select(['id', 'vehicle'])
                ->get()
                ->pluck('vehicle', 'id')
                ->toArray();
        }

        $query->orderBy('vehicle', 'asc');
        
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Get total count for this query
        $total = $query->count();
        
        // Get paginated results
        $results = $query->skip($offset)->take($perPage)->get();
        
        // Convert to array format - database ORDER BY ensures alphabetical order
        $data = $results->pluck('vehicle', 'id')->toArray();
        
        // Handle includeIds for label loading only (when explicitly requested with specific IDs)
        if (!empty($includeIds)) {
            $includedItems = Vehicle::whereIn('id', $includeIds)
                ->select(['id', 'vehicle'])
                ->orderBy('vehicle', 'asc')
                ->get()
                ->pluck('vehicle', 'id')
                ->toArray();
            return [
                'data' => $includedItems,
                'has_more' => false,
                'total' => count($includedItems),
            ];
        }
        
        return [
            'data' => $data,
            'has_more' => ($offset + $perPage) < $total,
            'total' => $total,
        ];
    }

    #[Renderless]
    public function getPaginatedDrivers($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = Driver::query()
            ->whereNull('deleted_at')
            ->where('disabled', false)
            ->select(['id', 'first_name', 'middle_name', 'last_name']);

        // Apply search filter
        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('first_name', 'like', $searchTerm)
                  ->orWhere('middle_name', 'like', $searchTerm)
                  ->orWhere('last_name', 'like', $searchTerm);
            });
        }

        // Include specific IDs (for selected items)
        if (!empty($includeIds)) {
            $includedItems = Driver::whereIn('id', $includeIds)
                ->select(['id', 'first_name', 'middle_name', 'last_name'])
                ->get()
                ->mapWithKeys(function ($driver) {
                    return [$driver->id => trim("{$driver->first_name} {$driver->middle_name} {$driver->last_name}")];
                })
                ->toArray();
        }

        $query->orderBy('first_name', 'asc');
        
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Get total count for this query
        $total = $query->count();
        
        // Get paginated results
        $results = $query->skip($offset)->take($perPage)->get();
        
        // Convert to array format - database ORDER BY ensures alphabetical order
        $data = $results->mapWithKeys(function ($driver) {
            return [$driver->id => trim("{$driver->first_name} {$driver->middle_name} {$driver->last_name}")];
        })->toArray();
        
        // Handle includeIds for label loading only
        if (!empty($includeIds)) {
            $includedItems = Driver::whereIn('id', $includeIds)
                ->select(['id', 'first_name', 'middle_name', 'last_name'])
                ->orderBy('first_name', 'asc')
                ->get()
                ->mapWithKeys(function ($driver) {
                    return [$driver->id => trim("{$driver->first_name} {$driver->middle_name} {$driver->last_name}")];
                })
                ->toArray();
            return [
                'data' => $includedItems,
                'has_more' => false,
                'total' => count($includedItems),
            ];
        }
        
        return [
            'data' => $data,
            'has_more' => ($offset + $perPage) < $total,
            'total' => $total,
        ];
    }

    #[Renderless]
    public function getPaginatedLocations($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = Location::query()
            ->whereNull('deleted_at')
            ->where('disabled', false)
            ->select(['id', 'location_name']);

        // Apply search filter
        if (!empty($search)) {
            $query->where('location_name', 'like', '%' . $search . '%');
        }

        // Include specific IDs (for selected items)
        if (!empty($includeIds)) {
            $includedItems = Location::whereIn('id', $includeIds)
                ->select(['id', 'location_name'])
                ->get()
                ->pluck('location_name', 'id')
                ->toArray();
        }

        $query->orderBy('location_name', 'asc');
        
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Get total count for this query
        $total = $query->count();
        
        // Get paginated results
        $results = $query->skip($offset)->take($perPage)->get();
        
        // Convert to array format - database ORDER BY ensures alphabetical order
        $data = $results->pluck('location_name', 'id')->toArray();
        
        // Handle includeIds for label loading only
        if (!empty($includeIds)) {
            $includedItems = Location::whereIn('id', $includeIds)
                ->select(['id', 'location_name'])
                ->orderBy('location_name', 'asc')
                ->get()
                ->pluck('location_name', 'id')
                ->toArray();
            return [
                'data' => $includedItems,
                'has_more' => false,
                'total' => count($includedItems),
            ];
        }
        
        return [
            'data' => $data,
            'has_more' => ($offset + $perPage) < $total,
            'total' => $total,
        ];
    }

    #[Renderless]
    public function getPaginatedGuards($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = User::query()
            ->where('user_type', 0)
            ->where('disabled', false)
            ->select(['id', 'first_name', 'middle_name', 'last_name', 'username']);

        // Apply search filter
        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('first_name', 'like', $searchTerm)
                  ->orWhere('middle_name', 'like', $searchTerm)
                  ->orWhere('last_name', 'like', $searchTerm)
                  ->orWhere('username', 'like', $searchTerm);
            });
        }

        // Include specific IDs (for selected items)
        if (!empty($includeIds)) {
            $includedItems = User::whereIn('id', $includeIds)
                ->where('user_type', 0)
                ->select(['id', 'first_name', 'middle_name', 'last_name', 'username'])
                ->get()
                ->mapWithKeys(function ($user) {
                    $name = trim("{$user->first_name} {$user->middle_name} {$user->last_name}");
                    return [$user->id => "{$name} @{$user->username}"];
                })
                ->toArray();
        }

        $query->orderBy('first_name', 'asc')->orderBy('last_name', 'asc');
        
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Get total count for this query
        $total = $query->count();
        
        // Get paginated results
        $results = $query->skip($offset)->take($perPage)->get();
        
        // Convert to array format - database ORDER BY ensures alphabetical order
        $data = $results->mapWithKeys(function ($user) {
            $name = trim("{$user->first_name} {$user->middle_name} {$user->last_name}");
            return [$user->id => "{$name} @{$user->username}"];
        })->toArray();
        
        // Handle includeIds for label loading only
        if (!empty($includeIds)) {
            $includedItems = User::whereIn('id', $includeIds)
                ->where('user_type', 0)
                ->select(['id', 'first_name', 'middle_name', 'last_name', 'username'])
                ->orderBy('first_name', 'asc')->orderBy('last_name', 'asc')
                ->get()
                ->mapWithKeys(function ($user) {
                    $name = trim("{$user->first_name} {$user->middle_name} {$user->last_name}");
                    return [$user->id => "{$name} @{$user->username}"];
                })
                ->toArray();
            return [
                'data' => $includedItems,
                'has_more' => false,
                'total' => count($includedItems),
            ];
        }
        
        return [
            'data' => $data,
            'has_more' => ($offset + $perPage) < $total,
            'total' => $total,
        ];
    }


    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function applySort($column)
    {
        if ($this->sortBy === $column) {
            // Toggle direction if clicking the same column
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // Set new column and default to ascending
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        
        $this->resetPage('page');
    }

    public function applyFilters()
    {
        // Use filterStatus directly - it's already an integer (0, 1, 2, 3, 4) or null
        // null = All Statuses (no filter), 0 = Pending, 1 = Disinfecting, 2 = In-Transit, 3 = Completed, 4 = Incomplete
        $this->appliedStatus = $this->filterStatus; // Already an int or null
        // Create new array instances to ensure Livewire detects the change
        // Convert string IDs to integers for proper filtering
        $this->appliedOrigin = array_values(array_map('intval', $this->filterOrigin ?? []));
        $this->appliedDestination = array_values(array_map('intval', $this->filterDestination ?? []));
        $this->appliedDriver = array_values(array_map('intval', $this->filterDriver ?? []));
        $this->appliedVehicle = array_values(array_map('intval', $this->filterVehicle ?? []));
        $this->appliedHatcheryGuard = array_values(array_map('intval', $this->filterHatcheryGuard ?? []));
        $this->appliedReceivedGuard = array_values(array_map('intval', $this->filterReceivedGuard ?? []));
        $this->appliedCreatedFrom = !empty($this->filterCreatedFrom) ? $this->filterCreatedFrom : null;
        $this->appliedCreatedTo = !empty($this->filterCreatedTo) ? $this->filterCreatedTo : null;
        
        // Update filters active status
        $this->filtersActive = 
            ($this->appliedStatus !== null) ||
            !empty($this->appliedOrigin) ||
            !empty($this->appliedDestination) ||
            !empty($this->appliedDriver) ||
            !empty($this->appliedVehicle) ||
            !empty($this->appliedHatcheryGuard) ||
            !empty($this->appliedReceivedGuard) ||
            $this->appliedCreatedFrom ||
            $this->appliedCreatedTo;
        
        $this->showFilters = false;
        $this->resetPage();
    }

    public function removeFilter($filterName)
    {
        // Clear both the applied and filter values
        switch($filterName) {
            case 'status':
                $this->appliedStatus = null;
                $this->filterStatus = null;
                break;
            case 'origin':
                $this->appliedOrigin = [];
                $this->filterOrigin = [];
                break;
            case 'destination':
                $this->appliedDestination = [];
                $this->filterDestination = [];
                break;
            case 'driver':
                $this->appliedDriver = [];
                $this->filterDriver = [];
                break;
            case 'vehicle':
                $this->appliedVehicle = [];
                $this->filterVehicle = [];
                break;
            case 'hatcheryGuard':
                $this->appliedHatcheryGuard = [];
                $this->filterHatcheryGuard = [];
                break;
            case 'receivedGuard':
                $this->appliedReceivedGuard = [];
                $this->filterReceivedGuard = [];
                break;
            case 'createdFrom':
                $this->appliedCreatedFrom = null;
                $this->filterCreatedFrom = null;
                break;
            case 'createdTo':
                $this->appliedCreatedTo = null;
                $this->filterCreatedTo = null;
                break;
        }
        
        $this->updateFiltersActive();
        $this->resetPage();
    }

    public function removeSpecificFilter($filterType, $valueToRemove)
    {
        switch($filterType) {
            case 'origin':
                $this->appliedOrigin = array_values(array_filter($this->appliedOrigin, function($id) use ($valueToRemove) {
                    return $id != $valueToRemove;
                }));
                $this->filterOrigin = $this->appliedOrigin;
                break;
            case 'destination':
                $this->appliedDestination = array_values(array_filter($this->appliedDestination, function($id) use ($valueToRemove) {
                    return $id != $valueToRemove;
                }));
                $this->filterDestination = $this->appliedDestination;
                break;
            case 'driver':
                $this->appliedDriver = array_values(array_filter($this->appliedDriver, function($id) use ($valueToRemove) {
                    return $id != $valueToRemove;
                }));
                $this->filterDriver = $this->appliedDriver;
                break;
            case 'vehicle':
                $this->appliedVehicle = array_values(array_filter($this->appliedVehicle, function($id) use ($valueToRemove) {
                    return $id != $valueToRemove;
                }));
                $this->filterVehicle = $this->appliedVehicle;
                break;
            case 'hatcheryGuard':
                $this->appliedHatcheryGuard = array_values(array_filter($this->appliedHatcheryGuard, function($id) use ($valueToRemove) {
                    return $id != $valueToRemove;
                }));
                $this->filterHatcheryGuard = $this->appliedHatcheryGuard;
                break;
            case 'receivedGuard':
                $this->appliedReceivedGuard = array_values(array_filter($this->appliedReceivedGuard, function($id) use ($valueToRemove) {
                    return $id != $valueToRemove;
                }));
                $this->filterReceivedGuard = $this->appliedReceivedGuard;
                break;
        }
        
        $this->updateFiltersActive();
        $this->resetPage();
    }

    public function updateFiltersActive()
    {
        // Check if any filters are actually applied
        // Important: 0 is a valid status (Pending), so check for null explicitly
        $this->filtersActive = 
            ($this->appliedStatus !== null) ||
            !empty($this->appliedOrigin) ||
            !empty($this->appliedDestination) ||
            !empty($this->appliedDriver) ||
            !empty($this->appliedVehicle) ||
            !empty($this->appliedHatcheryGuard) ||
            !empty($this->appliedReceivedGuard) ||
            $this->appliedCreatedFrom ||
            $this->appliedCreatedTo;
    }

    public function cancelFilters()
    {
        $this->showFilters = false;
    }

    public function clearFilters()
    {
        $this->filterStatus = null;
        $this->filterOrigin = [];
        $this->filterDestination = [];
        $this->filterDriver = [];
        $this->filterVehicle = [];
        $this->filterHatcheryGuard = [];
        $this->filterReceivedGuard = [];
        $this->filterCreatedFrom = null;
        $this->filterCreatedTo = null;
        
        // Clear search properties
        $this->searchFilterVehicle = '';
        $this->searchFilterDriver = '';
        $this->searchFilterOrigin = '';
        $this->searchFilterDestination = '';
        $this->searchFilterHatcheryGuard = '';
        $this->searchFilterReceivedGuard = '';
        
        $this->appliedStatus = null;
        $this->appliedOrigin = [];
        $this->appliedDestination = [];
        $this->appliedDriver = [];
        $this->appliedVehicle = [];
        $this->appliedHatcheryGuard = [];
        $this->appliedReceivedGuard = [];
        $this->appliedCreatedFrom = null;
        $this->appliedCreatedTo = null;
        
        // Clear exclude deleted items filter (set to false so pill disappears)
        $this->excludeDeletedItems = false;
        
        $this->filtersActive = false;
        $this->resetPage();
    }

    // ==================== DETAILS MODAL METHODS ====================

    public function openDetailsModal($id)
    {
        // Optimize relationship loading by only selecting needed fields
        // This significantly reduces memory usage with large datasets
        $this->selectedSlip = DisinfectionSlipModel::withTrashed()->with([
            'vehicle' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
            'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
            'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
            'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
            'reason:id,reason_text,is_disabled',
            'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
            'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); }
        ])->find($id);

        $this->showDetailsModal = true;
    }

    // ==================== MODAL DISPATCH METHODS ====================

    public function openCreateModal()
    {
        // Dispatch event to the SlipCreate component
        $this->dispatch('openCreateModal');
    }

    public function openEditModal($id = null)
    {
        // Load the slip if ID is provided (for other modals like details, delete)
        if ($id) {
            $this->selectedSlip = DisinfectionSlipModel::withTrashed()->with([
                'vehicle' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
                'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
                'reason:id,reason_text,is_disabled',
                'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
                'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); }
            ])->find($id);
        }
        // Re-fetch selectedSlip with withTrashed() to preserve deleted relations and find deleted slips
        elseif ($this->selectedSlip && $this->selectedSlip->id) {
            $this->selectedSlip = DisinfectionSlipModel::withTrashed()->with([
                'vehicle' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
                'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
                'reason:id,reason_text,is_disabled',
                'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
                'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); }
            ])->find($this->selectedSlip->id);
        }

        // Dispatch event to the SlipEdit component
        $slipId = $id ?? ($this->selectedSlip?->id);
        if ($slipId) {
            $this->dispatch('openEditModal', $slipId);
        }
    }

    public function openDeleteModal()
    {
        if (!$this->selectedSlip) {
            return;
        }

        // Dispatch event to the Slips Delete component
        $this->dispatch('openDeleteModal', $this->selectedSlip->id);
    }

    public function openRestoreModal($slipId)
    {
        // Dispatch event to the Restore component
        $this->dispatch('openRestoreModal', $slipId);
    }
    
    public function getSelectedSlipAttachmentsProperty()
    {
        if (!$this->selectedSlip || empty($this->selectedSlip->photo_ids)) {
            return collect([]);
        }

        // Optimize Photo loading by only selecting needed fields
        return Photo::whereIn('id', $this->selectedSlip->photo_ids)
            ->select('id', 'file_path', 'user_id', 'created_at', 'updated_at')
            ->with(['user' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'deleted_at')->withTrashed(); }])
            ->get();
    }

    public function getDisplayReasonProperty()
    {
        if (!$this->selectedSlip || !$this->selectedSlip->reason) {
            return 'N/A';
        }

        return $this->selectedSlip->reason->reason_text;
    }

    public function canEdit()
    {
        if (!$this->selectedSlip) {
            return false;
        }

        // Cannot edit if vehicle is soft-deleted
        if ($this->selectedSlip->vehicle && $this->selectedSlip->vehicle->trashed()) {
            return false;
        }

        // SuperAdmin can edit any slip, including completed ones (unless vehicle is soft-deleted)
        return true;
    }

    public function canDelete()
    {
        if (!$this->selectedSlip) {
            return false;
        }

        // Cannot delete if vehicle is soft-deleted
        if ($this->selectedSlip->vehicle && $this->selectedSlip->vehicle->trashed()) {
            return false;
        }

        // SuperAdmin can delete any slip, including completed ones (unless vehicle is soft-deleted)
        return true;
    }

    public function canRemoveAttachment()
    {
        if (!$this->selectedSlip) {
            return false;
        }

        // SuperAdmin can remove Photo from any slip, including completed ones
        $attachmentIds = $this->selectedSlip->photo_ids ?? [];
        return !empty($attachmentIds);
    }

    #[On('slip-updated')]
    public function handleSlipUpdated()
    {
        $this->resetPage();
        // Refresh selectedSlip if it exists
        if ($this->selectedSlip && $this->selectedSlip->id) {
            $this->selectedSlip = DisinfectionSlipModel::withTrashed()->with([
                'vehicle' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
                'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
                'reason:id,reason_text,is_disabled',
                'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
                'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); }
            ])->find($this->selectedSlip->id);
        }
    }
    


    #[On('slip-deleted')]
    public function handleSlipDeleted()
    {
        $this->resetPage();
        $this->showDetailsModal = false;
        $this->selectedSlip = null;
    }


    #[On('slip-restored')]
    public function handleSlipRestored()
    {
        $this->resetPage();
    }

    public function closeDetailsModal()
    {
        $this->showDeleteConfirmation = false;
        $this->showRemoveAttachmentConfirmation = false;
        $this->showDetailsModal = false;
        $this->js('setTimeout(() => $wire.clearSelectedSlip(), 300)');
        // Restore functionality moved to Shared\Slips\Restore component
    }

    public function clearSelectedSlip()
    {
        $this->selectedSlip = null;
    }

    public function openAttachmentModal($index = 0)
    {
        $this->currentAttachmentIndex = (int) $index;
        $this->showAttachmentModal = true;
    }

    public function closeAttachmentModal()
    {
        $this->showAttachmentModal = false;
        $this->currentAttachmentIndex = 0;

        // Livewire re-hydrates models without trashed relations; reload for details modal
        // Optimize relationship loading by only selecting needed fields
        if ($this->selectedSlip) {
            $this->selectedSlip = DisinfectionSlipModel::with([
                'vehicle' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
                'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
                'reason:id,reason_text,is_disabled',
                'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
                'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); }
            ])->find($this->selectedSlip->id);
        }
    }

    public function nextAttachment()
    {
        $photos = $this->selectedSlipAttachments;
        if ($this->currentAttachmentIndex < $photos->count() - 1) {
            $this->currentAttachmentIndex++;
        }
    }

    public function previousAttachment()
    {
        if ($this->currentAttachmentIndex > 0) {
            $this->currentAttachmentIndex--;
        }
    }

    public function confirmRemoveAttachment($attachmentId)
    {
        $this->attachmentToDelete = $attachmentId;
        $this->showRemoveAttachmentConfirmation = true;
    }

    public function removeAttachment()
    {
        try {
            if (!$this->canRemoveAttachment()) {
                $this->dispatch('toast', message: 'Cannot remove Photo from a completed slip.', type: 'error');
                return;
            }

            if (!$this->attachmentToDelete) {
                $this->dispatch('toast', message: 'No Photo specified to remove.', type: 'error');
                return;
            }

            // Get current Photo IDs
            $attachmentIds = $this->selectedSlip->photo_ids ?? [];
            
            if (empty($attachmentIds) || !in_array($this->attachmentToDelete, $attachmentIds)) {
                $this->dispatch('toast', message: 'Photo not found.', type: 'error');
                return;
            }

            // Get the Photo record
            $Photo = Photo::find($this->attachmentToDelete);

            if ($Photo) {
                // Delete the physical file from storage (except BGC.png logo)
                if ($Photo->file_path !== 'images/logo/BGC.png') {
                    if (Storage::disk('public')->exists($Photo->file_path)) {
                        Storage::disk('public')->delete($Photo->file_path);
                    }

                    // Log the Photo deletion
                    $oldValues = [
                        'file_path' => $Photo->file_path,
                        'user_id' => $Photo->user_id,
                        'disinfection_slip_id' => $this->selectedSlip->id,
                        'slip_number' => $this->selectedSlip->slip_number,
                    ];

                    Logger::delete(
                        Photo::class,
                        $Photo->id,
                        "Deleted Photo/photo from disinfection slip {$this->selectedSlip->slip_number}",
                        $oldValues,
                        ['related_slip' => $this->selectedSlip->id]
                    );

                    // Hard delete the Photo record
                    $Photo->forceDelete();
                }

                // Remove Photo ID from array
                $attachmentIds = array_values(array_filter($attachmentIds, fn($id) => $id != $this->attachmentToDelete));

                // Update slip with remaining Photo IDs (or null if empty)
                $this->selectedSlip->update([
                    'photo_ids' => empty($attachmentIds) ? null : $attachmentIds,
                ]);
            }

            // Refresh the slip
            $this->selectedSlip->refresh();

            // After deletion, close the Photo modal to avoid stale client-side indices.
            $this->showAttachmentModal = false;
            $this->currentAttachmentIndex = 0;

            // Close confirmation modal
            $this->showRemoveAttachmentConfirmation = false;
            $this->attachmentToDelete = null;

            $slipId = $this->selectedSlip->slip_id;
            $this->dispatch('toast', message: "Photo has been removed from {$slipId}.", type: 'success');

        } catch (\Exception $e) {
            Log::error('Photo removal error: ' . $e->getMessage());
            $this->dispatch('toast', message: 'Failed to remove Photo. Please try again.', type: 'error');
        }
    }

    public function toggleDeletedView()
    {
        $this->showDeleted = !$this->showDeleted;
        
        if ($this->showDeleted) {
            // Entering restore mode - save current date filters
            $this->previousFilterCreatedFrom = $this->filterCreatedFrom;
            $this->previousFilterCreatedTo = $this->filterCreatedTo;
            $this->previousAppliedCreatedFrom = $this->appliedCreatedFrom;
            $this->previousAppliedCreatedTo = $this->appliedCreatedTo;
            
            // Clear all filters except date range when entering restore mode
            $this->filterStatus = null;
            $this->filterOrigin = [];
            $this->filterDestination = [];
            $this->filterDriver = [];
            $this->filterVehicle = [];
            $this->filterHatcheryGuard = [];
            $this->filterReceivedGuard = [];
            
            $this->appliedStatus = null;
            $this->appliedOrigin = [];
            $this->appliedDestination = [];
            $this->appliedDriver = [];
            $this->appliedVehicle = [];
            $this->appliedHatcheryGuard = [];
            $this->appliedReceivedGuard = [];
            
            $this->updateFiltersActive();
        } else {
            // Exiting restore mode - restore previous date filters if they existed
            if ($this->previousFilterCreatedFrom !== null) {
                $this->filterCreatedFrom = $this->previousFilterCreatedFrom;
                $this->appliedCreatedFrom = $this->previousAppliedCreatedFrom;
            }
            if ($this->previousFilterCreatedTo !== null) {
                $this->filterCreatedTo = $this->previousFilterCreatedTo;
                $this->appliedCreatedTo = $this->previousAppliedCreatedTo;
            }
            
            // Clear the stored previous values
            $this->previousFilterCreatedFrom = null;
            $this->previousFilterCreatedTo = null;
            $this->previousAppliedCreatedFrom = null;
            $this->previousAppliedCreatedTo = null;
            
            $this->updateFiltersActive();
        }
        
        $this->resetPage();
    }

    /**
     * Sanitize text input (for textarea fields like remarks_for_disinfection)
     * Removes HTML tags, decodes entities, removes control characters
     * Preserves newlines and normalizes whitespace
     * 
     * @param string|null $text
     * @return string|null
     */
    private function sanitizeText($text)
    {
        if (empty($text)) {
            return null;
        }

        // Remove HTML tags
        $text = strip_tags($text);
        
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove control characters (but preserve newlines \n and carriage returns \r)
        $text = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/u', '', $text);
        
        // Normalize line endings to \n
        $text = preg_replace('/\r\n|\r/', "\n", $text);
        
        // Normalize multiple spaces to single space (but preserve newlines)
        $text = preg_replace('/[ \t]+/', ' ', $text);
        
        // Remove trailing whitespace from each line
        $lines = explode("\n", $text);
        $lines = array_map('rtrim', $lines);
        $text = implode("\n", $lines);
        
        // Trim the entire text
        return trim($text) ?: null;
    }

    public function render()
    {
        // Optimize relationship loading by only selecting needed fields
        // This significantly reduces memory usage with large datasets (5,000+ records)
        $query = $this->showDeleted
            ? DisinfectionSlipModel::onlyTrashed()->with([
                'vehicle:id,vehicle,disabled,deleted_at',
                'location:id,location_name,disabled,deleted_at',
                'destination:id,location_name,disabled,deleted_at',
                'driver:id,first_name,middle_name,last_name,disabled,deleted_at',
                'hatcheryGuard:id,first_name,middle_name,last_name,username,disabled,deleted_at',
                'receivedGuard:id,first_name,middle_name,last_name,username,disabled,deleted_at'
            ])
            : DisinfectionSlipModel::with([
                'vehicle:id,vehicle,disabled,deleted_at',
                'location:id,location_name,disabled,deleted_at',
                'destination:id,location_name,disabled,deleted_at',
                'driver:id,first_name,middle_name,last_name,disabled,deleted_at',
                'hatcheryGuard:id,first_name,middle_name,last_name,username,disabled,deleted_at',
                'receivedGuard:id,first_name,middle_name,last_name,username,disabled,deleted_at'
            ])->whereNull('deleted_at');
        
        $slips = $query
            // Search
            ->when($this->search, function($query) {
                // Sanitize search term to prevent SQL injection
                $searchTerm = trim($this->search);
                $searchTerm = preg_replace('/[%_]/', '', $searchTerm); // Remove LIKE wildcards for safety
                
                if (empty($searchTerm)) {
                    return;
                }
                
                // Escape special characters for LIKE
                $escapedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                
                $query->where(function($q) use ($escapedSearchTerm) {
                    $q->where('slip_id', 'like', '%' . $escapedSearchTerm . '%')
                        ->orWhereHas('vehicle', function($vehicleQuery) use ($escapedSearchTerm) {
                            $vehicleQuery->withTrashed()->where('vehicle', 'like', '%' . $escapedSearchTerm . '%');
                        })
                        ->orWhereHas('driver', function($driverQuery) use ($escapedSearchTerm) {
                            $driverQuery->withTrashed()->where(function($dq) use ($escapedSearchTerm) {
                                $dq->where('first_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhere('last_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%'])
                                ->orWhereRaw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%']);
                            });
                        })
                        ->orWhereHas('location', function($locationQuery) use ($escapedSearchTerm) {
                            $locationQuery->withTrashed()->where('location_name', 'like', '%' . $escapedSearchTerm . '%');
                        })
                        ->orWhereHas('destination', function($destinationQuery) use ($escapedSearchTerm) {
                            $destinationQuery->withTrashed()->where('location_name', 'like', '%' . $escapedSearchTerm . '%');
                        })
                        ->orWhereHas('hatcheryGuard', function($guardQuery) use ($escapedSearchTerm) {
                            $guardQuery->withTrashed()->where(function($gq) use ($escapedSearchTerm) {
                                $gq->where('first_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhere('middle_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhere('last_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%'])
                                ->orWhereRaw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%']);
                            });
                        })
                        ->orWhereHas('receivedGuard', function($guardQuery) use ($escapedSearchTerm) {
                            $guardQuery->withTrashed()->where(function($gq) use ($escapedSearchTerm) {
                                $gq->where('first_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhere('middle_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhere('last_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%'])
                                ->orWhereRaw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%']);
                            });
                        });
                });
            })
            // Status filter
            // Important: Check for null explicitly, as 0 is a valid status (Pending)
            ->when($this->filtersActive && $this->appliedStatus !== null, function($query) {
                // appliedStatus is already an integer (0, 1, 2, 3, or 4)
                $query->where('status', $this->appliedStatus);
            })
            // Origin filter
            ->when($this->filtersActive && !empty($this->appliedOrigin), function($query) {
                $query->whereIn('location_id', $this->appliedOrigin);
            })
            // Destination filter
            ->when($this->filtersActive && !empty($this->appliedDestination), function($query) {
                $query->whereIn('destination_id', $this->appliedDestination);
            })
            // Driver filter
            ->when($this->filtersActive && !empty($this->appliedDriver), function($query) {
                $query->whereIn('driver_id', $this->appliedDriver);
            })
            // Vehicle filter
            ->when($this->filtersActive && !empty($this->appliedVehicle), function($query) {
                $query->whereIn('vehicle_id', $this->appliedVehicle);
            })
            // Hatchery guard filter
            ->when(!empty($this->appliedHatcheryGuard), function($query) {
                $guardIds = array_map('intval', $this->appliedHatcheryGuard);
                if (!empty($guardIds)) {
                    $query->whereIn('hatchery_guard_id', $guardIds);
                }
            })
            // Received guard filter
            ->when(!empty($this->appliedReceivedGuard), function($query) {
                $guardIds = array_map('intval', $this->appliedReceivedGuard);
                if (!empty($guardIds)) {
                    $query->whereIn('received_guard_id', $guardIds);
                }
            })
            // Created date range filter (always apply if set, regardless of filtersActive flag)
            ->when($this->appliedCreatedFrom, function($query) {
                $query->whereDate('created_at', '>=', $this->appliedCreatedFrom);
            })
            ->when($this->appliedCreatedTo, function($query) {
                $query->whereDate('created_at', '<=', $this->appliedCreatedTo);
            })
            // Exclude slips with deleted items (default: on)
            // Use whereIn with subqueries for better performance than whereHas with large datasets
            // This avoids loading all IDs into memory and is faster than whereHas
            ->when($this->excludeDeletedItems, function($query) {
                $query->whereIn('vehicle_id', function($subquery) {
                        $subquery->select('id')->from('vehicles')->whereNull('deleted_at');
                    })
                    ->whereIn('driver_id', function($subquery) {
                        $subquery->select('id')->from('drivers')->whereNull('deleted_at');
                    })
                    ->whereIn('location_id', function($subquery) {
                        $subquery->select('id')->from('locations')->whereNull('deleted_at');
                    })
                    ->whereIn('destination_id', function($subquery) {
                        $subquery->select('id')->from('locations')->whereNull('deleted_at');
                    })
                    ->where(function($q) {
                        $q->whereIn('hatchery_guard_id', function($subquery) {
                            $subquery->select('id')->from('users')->whereNull('deleted_at');
                        })
                          ->orWhereNull('hatchery_guard_id');
                    })
                    ->where(function($q) {
                        $q->whereIn('received_guard_id', function($subquery) {
                            $subquery->select('id')->from('users')->whereNull('deleted_at');
                        })
                          ->orWhereNull('received_guard_id');
                    });
            })
            // Apply sorting (works with all filters)
            ->when($this->sortBy === 'slip_id' && !$this->showDeleted, function($query) {
                // For slip_id format "YY-00001", extract the numeric part (starts at position 4, after "YY-")
                // Sort by year first, then by number within that year
                $direction = strtoupper($this->sortDirection);
                $query->orderByRaw("SUBSTRING(slip_id, 1, 2) " . $direction) // Year part
                    ->orderByRaw("CAST(SUBSTRING(slip_id, 4) AS UNSIGNED) " . $direction); // Number part
            })
            ->when($this->sortBy !== 'slip_id' && !$this->showDeleted, function($query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
                // Add secondary sort by slip_id for consistent ordering when primary sort values are equal
                $direction = strtoupper($this->sortDirection);
                $query->orderByRaw("SUBSTRING(slip_id, 1, 2) " . $direction) // Year part
                    ->orderByRaw("CAST(SUBSTRING(slip_id, 4) AS UNSIGNED) " . $direction); // Number part
            })
            ->when($this->showDeleted, function ($query) {
                $query->orderBy('deleted_at', 'desc');
            })
            ->paginate(10);

        return view($this->viewPath, [
            'slips' => $slips,
            'locations' => $this->locations,
            'drivers' => $this->drivers,
            'vehicles' => $this->vehicles,
            'guards' => $this->guards,
            'availableStatuses' => $this->availableStatuses,
        ]);
    }

    public function getExportData()
    {
        $query = $this->showDeleted 
            ? DisinfectionSlipModel::onlyTrashed()->with(['vehicle' => function($q) { $q->withTrashed(); }, 'location' => function($q) { $q->withTrashed(); }, 'destination' => function($q) { $q->withTrashed(); }, 'driver' => function($q) { $q->withTrashed(); }, 'reason', 'hatcheryGuard' => function($q) { $q->withTrashed(); }, 'receivedGuard' => function($q) { $q->withTrashed(); }])
            : DisinfectionSlipModel::with(['vehicle' => function($q) { $q->withTrashed(); }, 'location' => function($q) { $q->withTrashed(); }, 'destination' => function($q) { $q->withTrashed(); }, 'driver' => function($q) { $q->withTrashed(); }, 'reason', 'hatcheryGuard' => function($q) { $q->withTrashed(); }, 'receivedGuard' => function($q) { $q->withTrashed(); }])->whereNull('deleted_at');
        
        return $query->when($this->search, function ($query) {
                $searchTerm = trim($this->search);
                $searchTerm = preg_replace('/[%_]/', '', $searchTerm);
                if (empty($searchTerm)) {
                    return;
                }
                $escapedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                $query->where(function($q) use ($escapedSearchTerm) {
                    $q->where('slip_id', 'like', '%' . $escapedSearchTerm . '%')
                        ->orWhereHas('vehicle', function($vehicleQuery) use ($escapedSearchTerm) {
                            $vehicleQuery->withTrashed()->where('vehicle', 'like', '%' . $escapedSearchTerm . '%');
                        })
                        ->orWhereHas('driver', function($driverQuery) use ($escapedSearchTerm) {
                            $driverQuery->withTrashed()->where(function($dq) use ($escapedSearchTerm) {
                                $dq->where('first_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhere('middle_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhere('last_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%'])
                                ->orWhereRaw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%']);
                            });
                        })
                        ->orWhereHas('location', function($locationQuery) use ($escapedSearchTerm) {
                            $locationQuery->withTrashed()->where('location_name', 'like', '%' . $escapedSearchTerm . '%');
                        })
                        ->orWhereHas('destination', function($destinationQuery) use ($escapedSearchTerm) {
                            $destinationQuery->withTrashed()->where('location_name', 'like', '%' . $escapedSearchTerm . '%');
                        });
                });
            })
            ->when($this->appliedStatus !== null, function($query) {
                $query->where('status', $this->appliedStatus);
            })
            ->when(!empty($this->appliedOrigin), function($query) {
                $query->whereIn('location_id', $this->appliedOrigin);
            })
            ->when(!empty($this->appliedDestination), function($query) {
                $query->whereIn('destination_id', $this->appliedDestination);
            })
            ->when(!empty($this->appliedDriver), function($query) {
                $query->whereIn('driver_id', $this->appliedDriver);
            })
            ->when(!empty($this->appliedVehicle), function($query) {
                $query->whereIn('vehicle_id', $this->appliedVehicle);
            })
            ->when(!empty($this->appliedHatcheryGuard), function($query) {
                $query->whereIn('hatchery_guard_id', $this->appliedHatcheryGuard);
            })
            ->when(!empty($this->appliedReceivedGuard), function($query) {
                $query->whereIn('received_guard_id', $this->appliedReceivedGuard);
            })
            ->when($this->appliedCreatedFrom, function($query) {
                $query->whereDate('created_at', '>=', $this->appliedCreatedFrom);
            })
            ->when($this->appliedCreatedTo, function($query) {
                $query->whereDate('created_at', '<=', $this->appliedCreatedTo);
            })
            ->when($this->sortBy === 'slip_id' && !$this->showDeleted, function($query) {
                $direction = strtoupper($this->sortDirection);
                $query->orderByRaw("SUBSTRING(slip_id, 1, 2) " . $direction)
                      ->orderByRaw("CAST(SUBSTRING(slip_id, 4) AS UNSIGNED) " . $direction);
            })
            ->when($this->sortBy !== 'slip_id' && !$this->showDeleted, function($query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            })
            ->when($this->showDeleted, function ($query) {
                $query->orderBy('deleted_at', 'desc');
            })
            // Exclude slips with deleted items (default: on)
            ->when($this->excludeDeletedItems, function($query) {
                $query->whereHas('vehicle', function($q) {
                    $q->whereNull('deleted_at');
                })
                ->whereHas('driver', function($q) {
                    $q->whereNull('deleted_at');
                })
                ->whereHas('location', function($q) {
                    $q->whereNull('deleted_at');
                })
                ->whereHas('destination', function($q) {
                    $q->whereNull('deleted_at');
                })
                ->where(function($q) {
                    $q->whereHas('hatcheryGuard', function($guardQ) {
                        $guardQ->whereNull('deleted_at');
                    })
                    ->orWhereNull('hatchery_guard_id');
                })
                ->where(function($q) {
                    $q->whereHas('receivedGuard', function($guardQ) {
                        $guardQ->whereNull('deleted_at');
                    })
                    ->orWhereNull('received_guard_id');
                });
            })
            ->get();
    }

    public function exportCSV()
    {
        $data = $this->getExportData();
        $filename = 'disinfection_slips_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'Photo; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['Slip ID', 'Vehicle', 'Origin', 'Destination', 'Driver', 'Status', 'Hatchery Guard', 'Received Guard', 'Created Date', 'Completed Date']);
            
            foreach ($data as $slip) {
                $statuses = ['Pending', 'Disinfecting', 'In-Transit', 'Completed', 'Incomplete'];
                $status = $statuses[$slip->status] ?? 'Unknown';
                $hatcheryGuard = $slip->hatcheryGuard ? trim(implode(' ', array_filter([$slip->hatcheryGuard->first_name, $slip->hatcheryGuard->middle_name, $slip->hatcheryGuard->last_name]))) : 'N/A';
                $receivedGuard = $slip->receivedGuard ? trim(implode(' ', array_filter([$slip->receivedGuard->first_name, $slip->receivedGuard->middle_name, $slip->receivedGuard->last_name]))) : 'N/A';
                $driver = $slip->driver ? trim(implode(' ', array_filter([$slip->driver->first_name, $slip->driver->middle_name, $slip->driver->last_name]))) : 'N/A';
                
                // Format vehicle with (Deleted) tag if vehicle is soft-deleted
                $vehicle = 'N/A';
                if ($slip->vehicle) {
                    $vehicle = $slip->vehicle->vehicle;
                    if ($slip->vehicle->trashed()) {
                        $vehicle .= ' (Deleted)';
                    }
                }
                
                fputcsv($file, [
                    $slip->slip_id,
                    $vehicle,
                    $slip->location->location_name ?? 'N/A',
                    $slip->destination->location_name ?? 'N/A',
                    $driver,
                    $status,
                    $hatcheryGuard,
                    $receivedGuard,
                    $slip->created_at->format('Y-m-d H:i:s'),
                    $slip->completed_at ? \Carbon\Carbon::parse($slip->completed_at)->format('Y-m-d H:i:s') : 'N/A'
                ]);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function openPrintView()
    {
        if ($this->showDeleted) {
            return;
        }

        $data = $this->getExportData();
        $exportData = $data->map(function($slip) {
            // Helper function to format names
            $formatName = function($user) {
                if (!$user) return 'N/A';
                $name = trim(implode(' ', array_filter([$user->first_name, $user->middle_name, $user->last_name])));
                return $user->trashed() ? $name . ' (Deleted)' : $name;
            };
            
            return [
                'slip_id' => $slip->slip_id,
                'vehicle' => $slip->vehicle ? ($slip->vehicle->trashed() ? $slip->vehicle->vehicle . ' (Deleted)' : $slip->vehicle->vehicle) : 'N/A',
                'origin' => $slip->location ? ($slip->location->trashed() ? $slip->location->location_name . ' (Deleted)' : $slip->location->location_name) : 'N/A',
                'destination' => $slip->destination ? ($slip->destination->trashed() ? $slip->destination->location_name . ' (Deleted)' : $slip->destination->location_name) : 'N/A',
                'driver' => $slip->driver ? ($slip->driver->trashed() ?
                    trim(implode(' ', array_filter([$slip->driver->first_name, $slip->driver->middle_name, $slip->driver->last_name]))) . ' (Deleted)' :
                    trim(implode(' ', array_filter([$slip->driver->first_name, $slip->driver->middle_name, $slip->driver->last_name])))) : 'N/A',
                'reason' => $slip->reason ? $slip->reason->reason_text : 'N/A',
                'status' => $slip->status,
                'hatchery_guard' => $formatName($slip->hatcheryGuard),
                'received_guard' => $formatName($slip->receivedGuard),
                'created_at' => $slip->created_at->toIso8601String(),
                'completed_at' => $slip->completed_at ? \Carbon\Carbon::parse($slip->completed_at)->toIso8601String() : null,
            ];
        })->toArray();
        
        $filters = [
            'search' => $this->search,
            'status' => $this->appliedStatus,
            'origin' => $this->appliedOrigin,
            'destination' => $this->appliedDestination,
            'driver' => $this->appliedDriver,
            'vehicle' => $this->appliedVehicle,
            'hatchery_guard' => $this->appliedHatcheryGuard,
            'received_guard' => $this->appliedReceivedGuard,
            'created_from' => $this->appliedCreatedFrom,
            'created_to' => $this->appliedCreatedTo,
        ];
        
        $sorting = [
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
        ];
        
        $token = Str::random(32);
        Session::put("export_data_{$token}", $exportData);
        Session::put("export_filters_{$token}", $filters);
        Session::put("export_sorting_{$token}", $sorting);
        Session::put("export_data_{$token}_expires", now()->addMinutes(10));
        
        $printUrl = route('superadmin.print.slips', ['token' => $token]);

        $this->js("window.open('{$printUrl}', '_blank')");
    }

    public function printSlip($slipId)
    {
        $token = Str::random(32);
        Session::put("print_slip_{$token}", $slipId);
        Session::put("print_slip_{$token}_expires", now()->addMinutes(10));

        $printUrl = route('superadmin.print.slip', ['token' => $token]);

        $this->js("window.open('{$printUrl}', '_blank')");
    }
    
    public function getPage()
    {
        return request()->get('page', 1);
    }

    
    
}
