<?php

namespace App\Livewire\SuperAdmin;

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
use App\Models\Reason;

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

    // Details Modal
    public $showDetailsModal = false;
    public $showAttachmentModal = false;
    public $showDeleteConfirmation = false;
    public $showRemoveAttachmentConfirmation = false;
    public $showRestoreModal = false;
    public $showDeleted = false; // Toggle to show deleted items

    // Reasons Modal
    public $showReasonsModal = false;
    public $showCreateReasonModal = false;

    #[Locked]
    public $selectedSlip = null;
    
    public $selectedSlipId = null;
    public $selectedSlipName = null;
    public $attachmentFile = null;
    public $currentAttachmentIndex = 0;
    public $attachmentToDelete = null;

    // Protection flags
    public $isDeleting = false;
    public $isRestoring = false;

    // Create Modal
    public $showCreateModal = false;
    public $showCancelCreateConfirmation = false;
    public $truck_id;
    public $location_id; // Origin
    public $destination_id;
    public $driver_id;
    public $hatchery_guard_id;
    public $received_guard_id = null; // Optional receiving guard for creation
    public $reason_id;
    public $remarks_for_disinfection;
    public $isCreating = false;
    public $isUpdating = false;
    public $newReasonText = '';
    public $editingReasonId = null;
    public $editingReasonText = '';
    public $originalReasonText = '';
    public $showSaveConfirmation = false;
    public $showUnsavedChangesConfirmation = false;
    public $savingReason = false;
    
    // Search properties for dropdowns (create modal)
    public $searchOrigin = '';
    public $searchDestination = '';
    public $searchTruck = '';
    public $searchDriver = '';
    public $searchHatcheryGuard = '';
    public $searchReceivedGuard = '';
    public $searchReason = '';
    
    // Search properties for details modal
    public $searchDetailsTruck = '';
    public $searchDetailsDestination = '';
    public $searchDetailsDriver = '';
    public $searchReasonSettings = '';
    public $filterReasonStatus = 'all'; // Filter: 'all', 'enabled', 'disabled'
    public $reasonsPage = 1; // Page for reasons pagination
    
    public function updatedSearchReasonSettings()
    {
        $this->reasonsPage = 1; // Reset to first page when search changes
    }

    public function updatedFilterReasonStatus()
    {
        $this->reasonsPage = 1; // Reset to first page when filter changes
    }
    
    // Edit Modal
    public $showEditModal = false;
    public $showCancelEditConfirmation = false;
    public $showFinalStatusConfirmation = false;
    public $editTruckId;
    public $editLocationId; // Origin (for status 0)
    public $editDestinationId;
    public $editDriverId;
    public $editHatcheryGuardId; // For status 0
    public $editReceivedGuardId = null;
    public $editReasonId;
    public $editRemarksForDisinfection;
    public $editStatus;
    
    // Search properties for edit modal
    public $searchEditTruck = '';
    public $searchEditOrigin = '';
    public $searchEditDestination = '';
    public $searchEditDriver = '';
    public $searchEditHatcheryGuard = '';
    public $searchEditReceivedGuard = '';
    public $searchEditReason = '';
    
    public $showDeleteReasonConfirmation = false;
    public $reasonToDelete = null;
    private $cachedFilterGuards = null;
    private $cachedFilterGuardsCollection = null;

    public function mount()
    {
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
     * Prevent polling from running when any modal is open
     * This prevents the selected slip data from being overwritten
     */
    #[On('polling')]
    public function polling()
    {
        // If any modal is open, skip polling
        if ($this->showFilters || $this->showCreateModal || $this->showDetailsModal || 
            $this->showDeleteConfirmation || $this->showRemoveAttachmentConfirmation || 
            $this->showEditModal || $this->showCancelCreateConfirmation || 
            $this->showCancelEditConfirmation || $this->showAttachmentModal || 
            $this->showRestoreModal || $this->showReasonsModal) {
            return;
        }
        
        // If a slip is selected, reload it with trashed relations (including if the slip itself is deleted)
        // Optimize relationship loading by only selecting needed fields
        if ($this->selectedSlip) {
            $this->selectedSlip = DisinfectionSlipModel::withTrashed()->with([
                'truck' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
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
    
    private function getCachedTrucks()
    {
        // Only cache id and vehicle to reduce memory usage with large datasets
        return Cache::remember('trucks_all', 300, function() {
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

    // Computed property for trucks - lazy load only what's needed
    public function getTrucksProperty()
    {
        // Only load trucks that are actually used in applied filters
        $truckIds = $this->filterVehicle ?? [];
        
        if (empty($truckIds)) {
            return collect();
        }
        
        // Only fetch the trucks we actually need
        return Vehicle::withTrashed()
            ->whereIn('id', $truckIds)
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
    // Old getFilterTruckOptionsProperty, getFilterDriverOptionsProperty, etc. removed

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
    public function getDetailsTruckOptionsProperty()
    {
        $trucks = $this->getCachedTrucks()->whereNull('deleted_at');
        $allOptions = $trucks->pluck('vehicle', 'id');
        $options = $allOptions;
        
        if (!empty($this->searchDetailsTruck)) {
            $searchTerm = strtolower($this->searchDetailsTruck);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected value is always included
            $options = $this->ensureSelectedInOptions($options, $this->truck_id, $allOptions);
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
    public function getPaginatedTrucks($search = '', $page = 1, $perPage = 20, $includeIds = [])
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

    #[Renderless]
    public function getPaginatedReasons($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = Reason::query()
            ->where('is_disabled', false)
            ->select(['id', 'reason_text']);

        // Apply search filter
        if (!empty($search)) {
            $query->where('reason_text', 'like', '%' . $search . '%');
        }

        // Include specific IDs (for selected items)
        if (!empty($includeIds)) {
            $includedItems = Reason::whereIn('id', $includeIds)
                ->select(['id', 'reason_text'])
                ->get()
                ->pluck('reason_text', 'id')
                ->toArray();
        }

        $query->orderBy('reason_text', 'asc');
        
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Get total count for this query
        $total = $query->count();
        
        // Get paginated results
        $results = $query->skip($offset)->take($perPage)->get();
        
        // Convert to array format - database ORDER BY ensures alphabetical order
        $data = $results->pluck('reason_text', 'id')->toArray();
        
        // Handle includeIds for label loading only
        if (!empty($includeIds)) {
            $includedItems = Reason::whereIn('id', $includeIds)
                ->select(['id', 'reason_text'])
                ->orderBy('reason_text', 'asc')
                ->get()
                ->pluck('reason_text', 'id')
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
            'truck' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
            'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
            'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
            'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
            'reason:id,reason_text,is_disabled',
            'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
            'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); }
        ])->find($id);

        $this->showDetailsModal = true;
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

    public function canEdit()
    {
        if (!$this->selectedSlip) {
            return false;
        }

        // Cannot edit if truck is soft-deleted
        if ($this->selectedSlip->truck && $this->selectedSlip->truck->trashed()) {
            return false;
        }

        // SuperAdmin can edit any slip, including completed ones (unless truck is soft-deleted)
        return true;
    }

    public function canDelete()
    {
        if (!$this->selectedSlip) {
            return false;
        }

        // Cannot delete if truck is soft-deleted
        if ($this->selectedSlip->truck && $this->selectedSlip->truck->trashed()) {
            return false;
        }

        // SuperAdmin can delete any slip, including completed ones (unless truck is soft-deleted)
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

    public function openEditModal($id = null)
    {
        // Load the slip if ID is provided (when called from table row)
        if ($id) {
            $this->selectedSlip = DisinfectionSlipModel::withTrashed()->with([
                'truck' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
                'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
                'reason:id,reason_text,is_disabled',
                'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
                'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); }
            ])->find($id);
        }
        // Re-fetch selectedSlip with withTrashed() to preserve deleted relations and find deleted slips
        // Optimize relationship loading by only selecting needed fields
        elseif ($this->selectedSlip && $this->selectedSlip->id) {
            $this->selectedSlip = DisinfectionSlipModel::withTrashed()->with([
                'truck' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
                'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
                'reason:id,reason_text,is_disabled',
                'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
                'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); }
            ])->find($this->selectedSlip->id);
        }

        // Load slip data into edit fields
        $this->editTruckId = $this->selectedSlip->truck_id;
        $this->editLocationId = $this->selectedSlip->location_id;
        $this->editDestinationId = $this->selectedSlip->destination_id;
        $this->editDriverId = $this->selectedSlip->driver_id;
        $this->editHatcheryGuardId = $this->selectedSlip->hatchery_guard_id;
        $this->editReceivedGuardId = $this->selectedSlip->received_guard_id;
        // Only set editReasonId if the reason exists and is not disabled
        $reasonId = $this->selectedSlip->reason_id;
        if ($reasonId) {
            $reason = Reason::find($reasonId);
            $this->editReasonId = ($reason && !$reason->is_disabled) ? $reasonId : null;
        } else {
            $this->editReasonId = null;
        }
        $this->editRemarksForDisinfection = $this->selectedSlip->remarks_for_disinfection;
        $this->editStatus = $this->selectedSlip->status;

        // Reset search properties
        $this->searchEditTruck = '';
        $this->searchEditOrigin = '';
        $this->searchEditDestination = '';
        $this->searchEditDriver = '';
        $this->searchEditHatcheryGuard = '';
        $this->searchEditReceivedGuard = '';
        $this->searchEditReason = '';

        $this->showEditModal = true;
    }
    
    public function updatedEditStatus($value)
    {
        // Status 0, 1, 2 (Pending, Disinfecting, In-Transit): Receiving guard is optional
        // Status 3 (Completed): Receiving guard is required (validation will handle this)
        if ($value == 0 || $value == 1 || $value == 2) {
            // Status 0, 1, 2: Receiving guard is optional
            // Keep it as is - user can manually clear it if needed
        } elseif ($value == 3) {
            // Status 3: Receiving guard is required
            // If it's null, we'll let validation handle the error
            // Don't auto-set it, let user choose
        }
    }

    public function closeEditModal()
    {
        // Re-fetch selectedSlip with withTrashed() to preserve deleted relations
        // Optimize relationship loading by only selecting needed fields
        if ($this->selectedSlip && $this->selectedSlip->id) {
            $this->selectedSlip = DisinfectionSlipModel::with([
                'truck' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
                'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
                'reason:id,reason_text,is_disabled',
                'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
                'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); }
            ])->find($this->selectedSlip->id);
        }
        
        // Check if form has unsaved changes
        if ($this->hasEditUnsavedChanges()) {
            $this->showCancelEditConfirmation = true;
        } else {
            $this->resetEditForm();
            $this->showEditModal = false;
        }
    }

    public function cancelEdit()
    {
        // Re-fetch selectedSlip with withTrashed() to preserve deleted relations after cancel (including if slip is deleted)
        // Optimize relationship loading by only selecting needed fields
        if ($this->selectedSlip && $this->selectedSlip->id) {
            $this->selectedSlip = DisinfectionSlipModel::withTrashed()->with([
                'truck' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
                'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
                'reason:id,reason_text,is_disabled',
                'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
                'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); }
            ])->find($this->selectedSlip->id);
        }
        $this->resetEditForm();
        $this->showCancelEditConfirmation = false;
        $this->showEditModal = false;
    }

    public function resetEditForm()
    {
        $this->editTruckId = null;
        $this->editLocationId = null;
        $this->editDestinationId = null;
        $this->editDriverId = null;
        $this->editHatcheryGuardId = null;
        $this->editReceivedGuardId = null;
        $this->editReasonId = null;
        $this->editRemarksForDisinfection = null;
        $this->editStatus = null;
        $this->searchEditTruck = '';
        $this->searchEditOrigin = '';
        $this->searchEditDestination = '';
        $this->searchEditDriver = '';
        $this->searchEditHatcheryGuard = '';
        $this->searchEditReceivedGuard = '';
        $this->searchEditReason = '';
        $this->resetErrorBag();
    }

    public function hasEditUnsavedChanges()
    {
        if (!$this->selectedSlip) {
            return false;
        }
        
        return $this->editTruckId != $this->selectedSlip->truck_id ||
               $this->editLocationId != $this->selectedSlip->location_id ||
               $this->editDestinationId != $this->selectedSlip->destination_id ||
               $this->editDriverId != $this->selectedSlip->driver_id ||
               $this->editHatcheryGuardId != $this->selectedSlip->hatchery_guard_id ||
               $this->editReceivedGuardId != $this->selectedSlip->received_guard_id ||
               $this->editReasonId != $this->selectedSlip->reason_id ||
               $this->editRemarksForDisinfection != $this->selectedSlip->remarks_for_disinfection ||
               $this->editStatus != $this->selectedSlip->status;
    }

    public function getHasChangesProperty()
    {
        return $this->hasEditUnsavedChanges();
    }

    public function confirmDeleteSlip()
    {
        $this->showDeleteConfirmation = true;
    }

    public function saveEdit()
    {
        // Prevent multiple submissions
        if ($this->isUpdating) {
            return;
        }

        $this->isUpdating = true;

        try {
        if (!$this->canEdit()) {
            $this->dispatch('toast', message: 'Cannot edit a completed slip.', type: 'error');
            return;
        }

        // Use the edited status, not the current status
        $status = $this->editStatus;
        
        // Validate status
        $this->validate([
            'editStatus' => 'required|in:0,1,2,3,4',
        ], [], [
            'editStatus' => 'Status',
        ]);
        
        // Build validation rules based on selected status
        $rules = [
            'editTruckId' => 'required|exists:trucks,id',
            'editDestinationId' => [
                'required',
                'exists:locations,id',
                function ($attribute, $value, $fail) {
                    if ($value == $this->editLocationId) {
                        $fail('The destination cannot be the same as the origin.');
                    }
                },
            ],
            'editDriverId' => 'required|exists:drivers,id',
            'editReasonId' => [
                'required',
                'exists:reasons,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $reason = Reason::find($value);
                        if (!$reason || $reason->is_disabled) {
                            $fail('The selected reason is not available.');
                        }
                    }
                },
            ],
            'editRemarksForDisinfection' => 'nullable|string|max:1000',
        ];

        // Status 0, 1, 2 (Pending, Disinfecting, In-Transit): Origin and Hatchery Guard are required, Receiving Guard is optional
        if ($status == 0 || $status == 1 || $status == 2) {
            $rules['editLocationId'] = [
                'required',
                'exists:locations,id',
                function ($attribute, $value, $fail) {
                    if ($value == $this->editDestinationId) {
                        $fail('The origin cannot be the same as the destination.');
                    }
                },
            ];
            $rules['editHatcheryGuardId'] = [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $guard = User::find($value);
                    if (!$guard) {
                        $fail('The selected hatchery guard does not exist.');
                        return;
                    }
                    if ($guard->user_type !== 0) {
                        $fail('The selected user is not a guard.');
                        return;
                    }
                    if ($guard->disabled) {
                        $fail('The selected hatchery guard has been disabled.');
                    }
                },
            ];
            $rules['editReceivedGuardId'] = [
                'nullable',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value && $value == $this->editHatcheryGuardId) {
                        $fail('The receiving guard cannot be the same as the hatchery guard.');
                        return;
                    }
                    if ($value) {
                        $guard = User::find($value);
                        if (!$guard) {
                            $fail('The selected receiving guard does not exist.');
                            return;
                        }
                        if ($guard->user_type !== 0) {
                            $fail('The selected user is not a guard.');
                            return;
                        }
                        if ($guard->disabled) {
                            $fail('The selected receiving guard has been disabled.');
                        }
                    }
                },
            ];
        }
        
        // Status 3 (Completed): Origin, Hatchery Guard, and Receiving Guard are all required
        if ($status == 3) {
            $rules['editLocationId'] = [
                'required',
                'exists:locations,id',
                function ($attribute, $value, $fail) {
                    if ($value == $this->editDestinationId) {
                        $fail('The origin cannot be the same as the destination.');
                    }
                },
            ];
            $rules['editHatcheryGuardId'] = [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $guard = User::find($value);
                    if (!$guard) {
                        $fail('The selected hatchery guard does not exist.');
                        return;
                    }
                    if ($guard->user_type !== 0) {
                        $fail('The selected user is not a guard.');
                        return;
                    }
                    if ($guard->disabled) {
                        $fail('The selected hatchery guard has been disabled.');
                    }
                },
            ];
            $rules['editReceivedGuardId'] = [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value && $value == $this->editHatcheryGuardId) {
                        $fail('The receiving guard cannot be the same as the hatchery guard.');
                        return;
                    }
                    $guard = User::find($value);
                    if (!$guard) {
                        $fail('The selected receiving guard does not exist.');
                        return;
                    }
                    if ($guard->user_type !== 0) {
                        $fail('The selected user is not a guard.');
                        return;
                    }
                    if ($guard->disabled) {
                        $fail('The selected receiving guard has been disabled.');
                    }
                },
            ];
        }

        $this->validate($rules, [], [
            'editTruckId' => 'Vehicle',
            'editLocationId' => 'Origin',
            'editDestinationId' => 'Destination',
            'editDriverId' => 'Driver',
            'editHatcheryGuardId' => 'Hatchery Guard',
            'editReceivedGuardId' => 'Receiving Guard',
            'editReasonId' => 'Reason',
            'editRemarksForDisinfection' => 'Remarks for Disinfection',
            'editStatus' => 'Status',
        ]);

        // Check if there are any changes
        if (!$this->hasEditUnsavedChanges()) {
            $this->dispatch('toast', message: 'No changes detected.', type: 'info');
            return;
        }

        // Sanitize remarks_for_disinfection
        $sanitizedRemarks = $this->sanitizeText($this->editRemarksForDisinfection);

        // Capture old values for logging
        $oldValues = $this->selectedSlip->only([
            'truck_id',
            'location_id',
            'destination_id',
            'driver_id',
            'hatchery_guard_id',
            'received_guard_id',
            'reason_id',
            'remarks_for_disinfection',
            'status'
        ]);

        // Build update data based on status
        $updateData = [
            'truck_id' => $this->editTruckId,
            'destination_id' => $this->editDestinationId,
            'driver_id' => $this->editDriverId,
            'reason_id' => $this->editReasonId,
            'remarks_for_disinfection' => $sanitizedRemarks,
            'status' => $this->editStatus,
        ];

        // Status 0, 1, 2: Update origin and hatchery guard, receiving guard is optional
        if ($status == 0 || $status == 1 || $status == 2) {
            $updateData['location_id'] = $this->editLocationId;
            $updateData['hatchery_guard_id'] = $this->editHatcheryGuardId;
            $updateData['received_guard_id'] = $this->editReceivedGuardId; // Can be null
        }
        
        // Status 3: Update origin, hatchery guard, and receiving guard (required)
        if ($status == 3) {
            $updateData['location_id'] = $this->editLocationId;
            $updateData['hatchery_guard_id'] = $this->editHatcheryGuardId;
            $updateData['received_guard_id'] = $this->editReceivedGuardId; // Required, validated above
        }

        // Handle completed_at based on status changes
        $oldStatus = $this->selectedSlip->status;
        $newStatus = $status;
        
        // Only update completed_at if status actually changed
        if ($oldStatus != $newStatus) {
            // If changing TO status 3 (Completed), set completed_at to current time
            if ($newStatus == 3) {
                $updateData['completed_at'] = now();
            }
            
            // If changing FROM status 3 (Completed) to any other status, clear completed_at
            if ($oldStatus == 3 && $newStatus != 3) {
                $updateData['completed_at'] = null;
            }
        }

        $this->selectedSlip->update($updateData);

        // Refresh the slip with relationships (including trashed relations)
        $this->selectedSlip->refresh();
        $this->selectedSlip->load([
            'truck' => function($q) { $q->withTrashed(); },
            'location' => function($q) { $q->withTrashed(); },
            'destination' => function($q) { $q->withTrashed(); },
            'driver' => function($q) { $q->withTrashed(); },
            'reason',
            'hatcheryGuard' => function($q) { $q->withTrashed(); },
            'receivedGuard' => function($q) { $q->withTrashed(); }
        ]);

        $slipId = $this->selectedSlip->slip_id;
        
        // Log the update action
        Logger::update(
            DisinfectionSlipModel::class,
            $this->selectedSlip->id,
            "Updated slip {$slipId}",
            $oldValues,
            $updateData
        );
        
        $this->resetEditForm();
        $this->showEditModal = false;
        $this->dispatch('toast', message: "{$slipId} has been updated.", type: 'success');
        } finally {
            $this->isUpdating = false;
        }
    }

    public function deleteSlip()
    {
        // Prevent multiple submissions
        if ($this->isDeleting) {
            return;
        }

        $this->isDeleting = true;

        try {
        if (!$this->canDelete()) {
            $this->dispatch('toast', message: 'Cannot delete a completed slip.', type: 'error');
            return;
        }

        $slipId = $this->selectedSlip->slip_id;
        $slipIdForLog = $this->selectedSlip->id;
        
        // Capture old values for logging
        $oldValues = $this->selectedSlip->only([
            'slip_id',
            'truck_id',
            'location_id',
            'destination_id',
            'driver_id',
            'hatchery_guard_id',
            'received_guard_id',
            'remarks_for_disinfection',
            'status'
        ]);
        
        // Clean up photos before soft deleting the slip
        $this->selectedSlip->deleteAttachments();
        
        // Atomic delete: Only delete if not already deleted to prevent race conditions
        $deleted = DisinfectionSlipModel::where('id', '=', $this->selectedSlip->id)
            ->whereNull('deleted_at') // Only delete if not already deleted
            ->update(['deleted_at' => now()]);
        
        if ($deleted === 0) {
            // Slip was already deleted by another process
            $this->showDeleteConfirmation = false;
            $this->dispatch('toast', message: 'This slip was already deleted by another administrator. Please refresh the page.', type: 'error');
            return;
        }
        
        // Log the delete action
        Logger::delete(
            DisinfectionSlipModel::class,
            $slipIdForLog,
            "Deleted slip {$slipId}",
            $oldValues
        );
        
        // Close all modals
        $this->showDeleteConfirmation = false;
        $this->showDetailsModal = false;
        
        // Clear selected slip
        $this->selectedSlip = null;
        
        // Show success message
        $this->dispatch('toast', message: "{$slipId} has been deleted.", type: 'success');
        
        // Reset page to refresh the list
        $this->resetPage();
        } finally {
            $this->isDeleting = false;
        }
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
            $this->appliedCreatedFrom = null;
            $this->appliedCreatedTo = null;
        } else {
            // Exiting restore mode: Always restore previous values, then reset stored values
            $this->filterCreatedFrom = $this->previousFilterCreatedFrom ?? '';
            $this->filterCreatedTo = $this->previousFilterCreatedTo ?? '';
            $this->appliedCreatedFrom = $this->previousAppliedCreatedFrom;
            $this->appliedCreatedTo = $this->previousAppliedCreatedTo;
            $this->filtersActive = ($this->appliedCreatedFrom || $this->appliedCreatedTo);
            
            // Reset stored values for next time
            $this->previousFilterCreatedFrom = null;
            $this->previousFilterCreatedTo = null;
            $this->previousAppliedCreatedFrom = null;
            $this->previousAppliedCreatedTo = null;
        }
        
        $this->resetPage();
    }

    public function openRestoreModal($slipId)
    {
        $slip = DisinfectionSlipModel::onlyTrashed()->findOrFail($slipId);
        $this->selectedSlipId = $slipId;
        $this->selectedSlipName = $slip->slip_id;
        $this->showRestoreModal = true;
    }

    public function restoreSlip()
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

        if (!$this->selectedSlipId) {
            return;
        }

        // Atomic restore: Only restore if currently deleted to prevent race conditions
        // Do the atomic update first, then load the model only if successful
        $restored = DisinfectionSlipModel::onlyTrashed()
            ->where('id', $this->selectedSlipId)
            ->update(['deleted_at' => null]);
        
        if ($restored === 0) {
            // Slip was already restored or doesn't exist
            $this->showRestoreModal = false;
            $this->reset(['selectedSlipId', 'selectedSlipName']);
            $this->dispatch('toast', message: 'This slip was already restored or does not exist. Please refresh the page.', type: 'error');
            $this->resetPage();
            return;
        }
        
        // Now load the restored slip
        $slip = DisinfectionSlipModel::findOrFail($this->selectedSlipId);
        
        // Log the restore action
        Logger::restore(
            DisinfectionSlipModel::class,
            $slip->id,
            "Restored disinfection slip {$slip->slip_id}"
        );
        
        $this->showRestoreModal = false;
        $this->reset(['selectedSlipId', 'selectedSlipName']);
        $this->resetPage();
        $this->dispatch('toast', message: "Disinfection slip {$slip->slip_id} has been restored.", type: 'success');
        } finally {
            $this->isRestoring = false;
        }
    }

    public function closeDetailsModal()
    {
        $this->showDeleteConfirmation = false;
        $this->showRemoveAttachmentConfirmation = false;
        $this->showRestoreModal = false;
        $this->showDetailsModal = false;
        $this->js('setTimeout(() => $wire.clearSelectedSlip(), 300)');
        $this->reset(['selectedSlipId', 'selectedSlipName']);
    }

    public function clearSelectedSlip()
    {
        $this->selectedSlip = null;
    }

    // ==================== CREATE MODAL METHODS ====================

    public function openCreateModal()
    {
        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        // Check if form has unsaved changes
        if ($this->hasUnsavedChanges()) {
            $this->showCancelCreateConfirmation = true;
        } else {
            $this->resetCreateForm();
            $this->showCreateModal = false;
        }
    }

    public function cancelCreate()
    {
        $this->resetCreateForm();
        $this->showCancelCreateConfirmation = false;
        $this->showCreateModal = false;
    }

    public function resetCreateForm()
    {
        $this->truck_id = null;
        $this->location_id = null;
        $this->destination_id = null;
        $this->driver_id = null;
        $this->hatchery_guard_id = null;
        $this->received_guard_id = null;
        $this->reason_id = null;
        $this->remarks_for_disinfection = null;
        $this->searchOrigin = '';
        $this->searchDestination = '';
        $this->searchTruck = '';
        $this->searchDriver = '';
        $this->searchHatcheryGuard = '';
        $this->searchReceivedGuard = '';
        $this->searchReason = '';
        $this->resetErrorBag();
    }

    public function hasUnsavedChanges()
    {
        return !empty($this->truck_id) || 
               !empty($this->location_id) || 
               !empty($this->destination_id) || 
               !empty($this->driver_id) || 
               !empty($this->hatchery_guard_id) || 
               !empty($this->received_guard_id) || 
               !empty($this->reason_id) ||
               !empty($this->remarks_for_disinfection);
    }

    public function createSlip()
    {
        // Prevent multiple submissions
        if ($this->isCreating) {
            return;
        }

        $this->isCreating = true;

        try {
        $this->validate([
            'truck_id' => 'required|exists:trucks,id',
            'location_id' => [
                'required',
                'exists:locations,id',
                function ($attribute, $value, $fail) {
                    if ($value == $this->destination_id) {
                        $fail('The origin cannot be the same as the destination.');
                    }
                },
            ],
            'destination_id' => [
                'required',
                'exists:locations,id',
                function ($attribute, $value, $fail) {
                    if ($value == $this->location_id) {
                        $fail('The destination cannot be the same as the origin.');
                    }
                },
            ],
            'driver_id' => 'required|exists:drivers,id',
            'hatchery_guard_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $guard = User::find($value);
                    if (!$guard) {
                        $fail('The selected hatchery guard does not exist.');
                        return;
                    }
                    if ($guard->user_type !== 0) {
                        $fail('The selected user is not a guard.');
                        return;
                    }
                    if ($guard->disabled) {
                        $fail('The selected hatchery guard has been disabled.');
                    }
                },
            ],
            'received_guard_id' => [
                'nullable',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value && $value == $this->hatchery_guard_id) {
                        $fail('The receiving guard cannot be the same as the hatchery guard.');
                        return;
                    }
                    if ($value) {
                        $guard = User::find($value);
                        if (!$guard) {
                            $fail('The selected receiving guard does not exist.');
                            return;
                        }
                        if ($guard->user_type !== 0) {
                            $fail('The selected user is not a guard.');
                            return;
                        }
                        if ($guard->disabled) {
                            $fail('The selected receiving guard has been disabled.');
                        }
                    }
                },
            ],
            'reason_id' => [
                'required',
                'exists:reasons,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $reason = Reason::find($value);
                        if (!$reason || $reason->is_disabled) {
                            $fail('The selected reason is not available.');
                        }
                    }
                },
            ],
            'remarks_for_disinfection' => 'nullable|string|max:1000',
        ], [], [
            'location_id' => 'Origin',
            'destination_id' => 'Destination',
            'truck_id' => 'Truck',
            'driver_id' => 'Driver',
            'hatchery_guard_id' => 'Hatchery Guard',
            'received_guard_id' => 'Receiving Guard',
            'reason_id' => 'Reason',
            'remarks_for_disinfection' => 'Remarks for Disinfection',
        ]);

        // Sanitize remarks_for_disinfection
        $sanitizedRemarks = $this->sanitizeText($this->remarks_for_disinfection);

        $slip = DisinfectionSlipModel::create([
            'truck_id' => $this->truck_id,
            'location_id' => $this->location_id,
            'destination_id' => $this->destination_id,
            'driver_id' => $this->driver_id,
            'hatchery_guard_id' => $this->hatchery_guard_id,
            'received_guard_id' => $this->received_guard_id,
            'reason_id' => $this->reason_id,
            'remarks_for_disinfection' => $sanitizedRemarks,
            'status' => 0, // Pending
        ]);

        $slipId = $slip->slip_id;
        
        // Log the create action
        Logger::create(
            DisinfectionSlipModel::class,
            $slip->id,
            "Created slip {$slipId}",
            $slip->only([
                'truck_id',
                'location_id',
                'destination_id',
                'driver_id',
                'hatchery_guard_id',
                'received_guard_id',
                'reason_id',
                'remarks_for_disinfection',
                'status'
            ])
        );
        
        $this->dispatch('toast', message: "{$slipId} has been created.", type: 'success');
        
        // Close modal and reset form
        $this->showCreateModal = false;
        $this->resetCreateForm();
        $this->resetPage();
        } finally {
            $this->isCreating = false;
        }
    }

    // Watch for changes to location_id or destination_id to prevent same selection
    public function updatedLocationId()
    {
        // If destination is the same as origin, clear it
        if ($this->destination_id == $this->location_id) {
            $this->destination_id = null;
        }
        // Don't clear search - dropdowns manage their own state
    }

    public function updatedDestinationId()
    {
        // If origin is the same as destination, clear it
        if ($this->location_id == $this->destination_id) {
            $this->location_id = null;
        }
        // Don't clear search - dropdowns manage their own state
    }

    public function updatedHatcheryGuardId()
    {
        // If receiving guard is the same as hatchery guard, clear it
        if ($this->received_guard_id == $this->hatchery_guard_id) {
            $this->received_guard_id = null;
        }
        // Don't clear search - dropdowns manage their own state
    }

    public function updatedReceivedGuardId()
    {
        // If receiving guard is set to hatchery guard, clear the hatchery guard
        if ($this->received_guard_id == $this->hatchery_guard_id) {
            $this->hatchery_guard_id = null;
        }
        // Don't clear search - dropdowns manage their own state
    }

    public function updatedEditLocationId()
    {
        // If destination is the same as origin, clear it
        if ($this->editDestinationId == $this->editLocationId) {
            $this->editDestinationId = null;
        }
        // Don't clear search - dropdowns manage their own state
    }

    public function updatedEditDestinationId()
    {
        // If origin is the same as destination, clear it
        if ($this->editLocationId == $this->editDestinationId) {
            $this->editLocationId = null;
        }
        // Don't clear search - dropdowns manage their own state
    }

    public function updatedEditHatcheryGuardId()
    {
        // If receiving guard is the same as hatchery guard, clear it
        if ($this->editReceivedGuardId == $this->editHatcheryGuardId) {
            $this->editReceivedGuardId = null;
        }
        // Don't clear search - dropdowns manage their own state
    }

    public function updatedEditReceivedGuardId()
    {
        // If receiving guard is set to hatchery guard, clear the hatchery guard
        if ($this->editReceivedGuardId == $this->editHatcheryGuardId) {
            $this->editHatcheryGuardId = null;
        }
        // Don't clear search - dropdowns manage their own state
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
                'truck' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
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
                'truck:id,vehicle,disabled,deleted_at',
                'location:id,location_name,disabled,deleted_at',
                'destination:id,location_name,disabled,deleted_at',
                'driver:id,first_name,middle_name,last_name,disabled,deleted_at',
                'hatcheryGuard:id,first_name,middle_name,last_name,username,disabled,deleted_at',
                'receivedGuard:id,first_name,middle_name,last_name,username,disabled,deleted_at'
            ])
            : DisinfectionSlipModel::with([
                'truck:id,vehicle,disabled,deleted_at',
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
                        ->orWhereHas('truck', function($truckQuery) use ($escapedSearchTerm) {
                            $truckQuery->withTrashed()->where('vehicle', 'like', '%' . $escapedSearchTerm . '%');
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
                $query->whereIn('truck_id', $this->appliedVehicle);
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
                $query->whereIn('truck_id', function($subquery) {
                        $subquery->select('id')->from('trucks')->whereNull('deleted_at');
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

        return view('livewire.super-admin.slips', [
            'slips' => $slips,
            'locations' => $this->locations,
            'drivers' => $this->drivers,
            'trucks' => $this->trucks,
            'guards' => $this->guards,
            'availableStatuses' => $this->availableStatuses,
            // NOTE: Filter/Create/Edit options removed - now using paginated dropdowns
            // NOTE: Reasons removed - loaded lazily by reason-settings modal
        ]);
    }

    public function getExportData()
    {
        $query = $this->showDeleted 
            ? DisinfectionSlipModel::onlyTrashed()->with(['truck' => function($q) { $q->withTrashed(); }, 'location' => function($q) { $q->withTrashed(); }, 'destination' => function($q) { $q->withTrashed(); }, 'driver' => function($q) { $q->withTrashed(); }, 'reason', 'hatcheryGuard' => function($q) { $q->withTrashed(); }, 'receivedGuard' => function($q) { $q->withTrashed(); }])
            : DisinfectionSlipModel::with(['truck' => function($q) { $q->withTrashed(); }, 'location' => function($q) { $q->withTrashed(); }, 'destination' => function($q) { $q->withTrashed(); }, 'driver' => function($q) { $q->withTrashed(); }, 'reason', 'hatcheryGuard' => function($q) { $q->withTrashed(); }, 'receivedGuard' => function($q) { $q->withTrashed(); }])->whereNull('deleted_at');
        
        return $query->when($this->search, function ($query) {
                $searchTerm = trim($this->search);
                $searchTerm = preg_replace('/[%_]/', '', $searchTerm);
                if (empty($searchTerm)) {
                    return;
                }
                $escapedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                $query->where(function($q) use ($escapedSearchTerm) {
                    $q->where('slip_id', 'like', '%' . $escapedSearchTerm . '%')
                        ->orWhereHas('truck', function($truckQuery) use ($escapedSearchTerm) {
                            $truckQuery->withTrashed()->where('vehicle', 'like', '%' . $escapedSearchTerm . '%');
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
                $query->whereIn('truck_id', $this->appliedVehicle);
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
                $query->whereHas('truck', function($q) {
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
                
                // Format vehicle with (Deleted) tag if truck is soft-deleted
                $vehicle = 'N/A';
                if ($slip->truck) {
                    $vehicle = $slip->truck->vehicle;
                    if ($slip->truck->trashed()) {
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
                'vehicle' => $slip->truck ? ($slip->truck->trashed() ? $slip->truck->vehicle . ' (Deleted)' : $slip->truck->vehicle) : 'N/A',
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
        
        $printUrl = route('superadmin.print.trucks', ['token' => $token]);
        
        $this->dispatch('open-print-window', ['url' => $printUrl]);
    }

    public function printSlip($slipId)
    {
        $token = Str::random(32);
        Session::put("print_slip_{$token}", $slipId);
        Session::put("print_slip_{$token}_expires", now()->addMinutes(10));
        
        $printUrl = route('superadmin.print.slip', ['token' => $token]);
        
        $this->dispatch('open-print-window', ['url' => $printUrl]);
    }


    public function getReasonsProperty()
    {
        $query = Reason::query()
            ->select(['id', 'reason_text', 'is_disabled'])
            ->orderBy('reason_text', 'asc');

        // Filter by status if not 'all'
        if ($this->filterReasonStatus !== 'all') {
            $isDisabled = $this->filterReasonStatus === 'disabled';
            $query->where('is_disabled', $isDisabled);
        }

        // Filter by search term if provided
        if (!empty($this->searchReasonSettings)) {
            $searchTerm = strtolower(trim($this->searchReasonSettings));
            $query->whereRaw('LOWER(reason_text) LIKE ?', ['%' . $searchTerm . '%']);
        }

        // Use database pagination
        $perPage = 5;
        return $query->paginate($perPage, ['*'], 'page', $this->reasonsPage);
    }
    
    // Separate pagination methods for reasons (don't override default pagination)
    public function gotoReasonsPage($page)
    {
        $this->reasonsPage = $page;
    }
    
    public function previousReasonsPage()
    {
        if ($this->reasonsPage > 1) {
            $this->reasonsPage--;
        }
    }
    
    public function nextReasonsPage()
    {
        $this->reasonsPage++;
    }
    
    public function getPage()
    {
        return request()->get('page', 1);
    }

    public function openCreateReasonModal()
    {
        $this->newReasonText = '';
        $this->showCreateReasonModal = true;
    }
    
    public function closeCreateReasonModal()
    {
        $this->newReasonText = '';
        $this->showCreateReasonModal = false;
        $this->resetErrorBag();
    }
    
    public function createReason()
    {
        // Validate the new reason text
        $this->validate([
            'newReasonText' => [
                'required',
                'string',
                'max:255',
                'min:1',
                function ($attribute, $value, $fail) {
                    $trimmedValue = trim($value);
                    $exists = Reason::whereRaw('LOWER(reason_text) = ?', [strtolower($trimmedValue)])
                        ->exists();
                    if ($exists) {
                        $fail('This reason already exists.');
                    }
                },
            ],
        ], [], [
            'newReasonText' => 'Reason text',
        ]);
        
        $reason = Reason::create([
            'reason_text' => trim($this->newReasonText),
            'disabled' => false,
        ]);
        
        // Log the create action
        Logger::create(
            Reason::class,
            $reason->id,
            "Added new reason: {$reason->reason_text}",
            $reason->only(['reason_text', 'is_disabled'])
        );
        
        $this->dispatch('toast', message: 'Reason created successfully.', type: 'success');
        
        $this->closeCreateReasonModal();
        
        // Reset to first page to show the new reason
        $this->resetPage();
    }

    public function startEditingReason($reasonId)
    {
        $reason = Reason::find($reasonId);
        
        if ($reason) {
            $this->editingReasonId = $reasonId;
            $this->editingReasonText = $reason->reason_text;
            $this->originalReasonText = $reason->reason_text;
        }
    }

    public function saveReasonEdit()
    {
        // Validate the edited text
        try {
            $this->validate([
                'editingReasonText' => [
                    'required',
                    'string',
                    'max:255',
                    'min:1',
                    function ($attribute, $value, $fail) {
                        $trimmedValue = trim($value);
                        $exists = Reason::where('id', '!=', $this->editingReasonId)
                            ->whereRaw('LOWER(reason_text) = ?', [strtolower($trimmedValue)])
                            ->exists();
                        if ($exists) {
                            $fail('This reason already exists.');
                        }
                    },
                ],
            ], [], [
                'editingReasonText' => 'Reason text',
            ]);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $firstError = collect($errors)->flatten()->first();
            if ($firstError) {
                $this->dispatch('toast', message: $firstError, type: 'error');
            }
            throw $e;
        }
        
        // Check if there are changes
        if (trim($this->editingReasonText) === $this->originalReasonText) {
            $this->dispatch('toast', message: 'No changes detected.', type: 'info');
            $this->cancelEditing();
            return;
        }
        
        // Show confirmation modal
        $this->showSaveConfirmation = true;
    }

    public function confirmSaveReasonEdit()
    {
        $this->savingReason = true;

        $reason = Reason::find($this->editingReasonId);

        if ($reason) {
            // Capture old values for logging
            $oldValues = $reason->only(['reason_text', 'is_disabled']);
            
            $reason->reason_text = trim($this->editingReasonText);
            $reason->save();

            // Log the update action
            Logger::update(
                Reason::class,
                $reason->id,
                "Updated reason: {$reason->reason_text}",
                $oldValues,
                $reason->only(['reason_text', 'is_disabled'])
            );

            $this->dispatch('toast', message: 'Reason updated successfully.', type: 'success');
        }

        // Reset editing state
        $this->showSaveConfirmation = false;
        $this->cancelEditing();

        // Refresh the page
        $this->resetPage();

        $this->savingReason = false;
    }

    public function cancelEditing()
    {
        $this->editingReasonId = null;
        $this->editingReasonText = '';
        $this->originalReasonText = '';
    }

    public function toggleReasonDisabled($reasonId)
    {
        $reason = Reason::find($reasonId);
        
        if ($reason) {
            // Capture old values for logging
            $oldValues = $reason->only(['reason_text', 'is_disabled']);
            
            $reason->disabled = !$reason->disabled;
            $reason->save();
            
            // Log the update action
            Logger::update(
                Reason::class,
                $reason->id,
                ($reason->disabled ? "Disabled reason: {$reason->reason_text}" : "Enabled reason: {$reason->reason_text}"),
                $oldValues,
                $reason->only(['reason_text', 'is_disabled'])
            );
            
            $status = $reason->disabled ? 'disabled' : 'enabled';
            $this->dispatch('toast', message: "Reason {$status} successfully.", type: 'success');
            
            // Refresh the page to show updated state
            $this->resetPage();
        }
    }

    public function confirmDeleteReason($reasonId)
    {
        $this->reasonToDelete = $reasonId;
        $this->showDeleteReasonConfirmation = true;
    }

    public function deleteReason()
    {
        if (!$this->reasonToDelete) {
            return;
        }
        
        $reason = Reason::find($this->reasonToDelete);
        
        if ($reason) {
            // Capture old values for logging
            $oldValues = $reason->only(['reason_text', 'is_disabled']);
            $reasonText = $reason->reason_text;
            $reasonId = $reason->id;
            
            $reason->delete();
            
            // Log the delete action
            Logger::delete(
                Reason::class,
                $reasonId,
                "Deleted reason: {$reasonText}",
                $oldValues
            );
            
            $this->dispatch('toast', message: 'Reason deleted successfully.', type: 'success');
        }
        
        $this->showDeleteReasonConfirmation = false;
        $this->reasonToDelete = null;
    }

    public function attemptCloseReasonsModal()
    {
        // Check if there are unsaved changes
        if ($this->editingReasonId !== null) {
            $this->showUnsavedChangesConfirmation = true;
        } else {
            $this->closeReasonsModal();
        }
    }

    public function closeWithoutSaving()
    {
        $this->showUnsavedChangesConfirmation = false;
        $this->cancelEditing();
        $this->closeReasonsModal();
    }

    public function closeReasonsModal()
    {
        $this->newReasonText = '';
        $this->searchReasonSettings = '';
        $this->cancelEditing();
        $this->showReasonsModal = false;
        $this->showDeleteReasonConfirmation = false;
        $this->showSaveConfirmation = false;
        $this->showUnsavedChangesConfirmation = false;
        $this->reasonToDelete = null;
    }

    public function openReasonsModal()
    {
        $this->newReasonText = '';
        $this->searchReasonSettings = '';
        $this->cancelEditing();
        $this->showReasonsModal = true;
        $this->showDeleteReasonConfirmation = false;
        $this->showSaveConfirmation = false;
        $this->showUnsavedChangesConfirmation = false;
        $this->reasonToDelete = null;
    }
}