<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\DisinfectionSlip as DisinfectionSlipModel;
use App\Models\Attachment;
use App\Models\Truck;
use App\Models\Location;
use App\Models\Driver;
use App\Models\User;
use App\Services\Logger;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log as FacadesLog;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class Trucks extends Component
{
    use WithPagination;

    public $search = '';
    public $showFilters = false;
    
    // Sorting properties
    public $sortBy = 'slip_id'; // Default sort by slip_id
    public $sortDirection = 'desc'; // Default descending
    
    // Filter fields
    public $filterStatus = null; // null = All Statuses, 0 = Pending, 1 = Disinfecting, 2 = Ongoing, 3 = Completed
    
    // Ensure filterStatus is properly typed when updated
    public function updatedFilterStatus($value)
    {
        // Handle null, empty string, or numeric values (0, 1, 2, 3 matching backend)
        // null/empty = All Statuses, 0 = Pending, 1 = Disinfecting, 2 = Ongoing, 3 = Completed
        // The select will send values as strings, so we convert to int
        if ($value === null || $value === '' || $value === false) {
            $this->filterStatus = null;
        } elseif (is_numeric($value)) {
            $intValue = (int)$value;
            if ($intValue >= 0 && $intValue <= 3) {
                // Store as integer (0, 1, 2, or 3)
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
    public $filterPlateNumber = [];
    public $filterHatcheryGuard = [];
    public $filterReceivedGuard = [];
    public $filterCreatedFrom = '';
    public $filterCreatedTo = '';
    
    // Search properties for filter dropdowns
    public $searchFilterPlateNumber = '';
    public $searchFilterDriver = '';
    public $searchFilterOrigin = '';
    public $searchFilterDestination = '';
    public $searchFilterHatcheryGuard = '';
    public $searchFilterReceivedGuard = '';
    
    // Applied filters (stored separately)
    public $appliedStatus = null; // null = All Statuses, 0 = Pending, 1 = Disinfecting, 2 = Ongoing, 3 = Completed
    public $appliedOrigin = [];
    public $appliedDestination = [];
    public $appliedDriver = [];
    public $appliedPlateNumber = [];
    public $appliedHatcheryGuard = [];
    public $appliedReceivedGuard = [];
    public $appliedCreatedFrom = null;
    public $appliedCreatedTo = null;

    public $filtersActive = false;
    public $excludeDeletedItems = true; // Default: exclude slips with deleted related items
    
    public $availableStatuses = [
        0 => 'Pending',
        1 => 'Disinfecting',
        2 => 'In-Transit',
        3 => 'Completed',
    ];

    // Details Modal
    public $showDetailsModal = false;
    public $showAttachmentModal = false;
    public $showDeleteConfirmation = false;
    public $showRemoveAttachmentConfirmation = false;
    public $selectedSlip = null;
    public $attachmentFile = null;
    public $currentAttachmentIndex = 0;
    public $attachmentToDelete = null;

    // Protection flags
    public $isDeleting = false;

    // Create Modal
    public $showCreateModal = false;
    public $showCancelCreateConfirmation = false;
    public $truck_id;
    public $location_id; // Origin
    public $destination_id;
    public $driver_id;
    public $hatchery_guard_id;
    public $received_guard_id = null; // Optional receiving guard for creation
    public $reason_for_disinfection;
    public $isCreating = false;
    
    // Search properties for dropdowns (create modal)
    public $searchOrigin = '';
    public $searchDestination = '';
    public $searchTruck = '';
    public $searchDriver = '';
    public $searchHatcheryGuard = '';
    public $searchReceivedGuard = '';
    
    // Search properties for details modal
    public $searchDetailsTruck = '';
    public $searchDetailsDestination = '';
    public $searchDetailsDriver = '';
    
    // Edit Modal
    public $showEditModal = false;
    public $showCancelEditConfirmation = false;
    public $editTruckId;
    public $editLocationId; // Origin (for status 0)
    public $editDestinationId;
    public $editDriverId;
    public $editHatcheryGuardId; // For status 0
    public $editReceivedGuardId = null;
    public $editReasonForDisinfection;
    public $editStatus;
    
    // Search properties for edit modal
    public $searchEditTruck = '';
    public $searchEditOrigin = '';
    public $searchEditDestination = '';
    public $searchEditDriver = '';
    public $searchEditHatcheryGuard = '';
    public $searchEditReceivedGuard = '';
    
    // Note: availableOriginsOptions and availableDestinationsOptions are now computed properties
    
    // Cached collections to avoid duplicate queries
    private $cachedLocations = null;
    private $cachedDrivers = null;
    private $cachedTrucks = null;
    private $cachedGuards = null;
    private $cachedFilterGuards = null;
    private $cachedFilterGuardsCollection = null;

    public function mount()
    {
        // Initialize array filters
        $this->filterOrigin = [];
        $this->filterDestination = [];
        $this->filterDriver = [];
        $this->filterPlateNumber = [];
        $this->filterHatcheryGuard = [];
        $this->filterReceivedGuard = [];
        $this->appliedOrigin = [];
        $this->appliedDestination = [];
        $this->appliedDriver = [];
        $this->appliedPlateNumber = [];
        $this->appliedHatcheryGuard = [];
        $this->appliedReceivedGuard = [];
        
        // Set default filter to today's date
        $today = now()->format('Y-m-d');
        $this->filterCreatedFrom = $today;
        $this->filterCreatedTo = $today;
        $this->appliedCreatedFrom = $today;
        $this->appliedCreatedTo = $today;
        $this->filtersActive = true;
        
        // Options are now computed properties, no initialization needed
    }
    
    // Helper methods to get cached collections
    private function getCachedLocations()
    {
        if ($this->cachedLocations === null) {
            $this->cachedLocations = Location::orderBy('location_name')->get();
        }
        return $this->cachedLocations;
    }
    
    private function getCachedDrivers()
    {
        if ($this->cachedDrivers === null) {
            $this->cachedDrivers = Driver::orderBy('first_name')->get();
        }
        return $this->cachedDrivers;
    }
    
    private function getCachedTrucks()
    {
        if ($this->cachedTrucks === null) {
            $this->cachedTrucks = Truck::withTrashed()->orderBy('plate_number')->get();
        }
        return $this->cachedTrucks;
    }
    
    private function getCachedGuards()
    {
        if ($this->cachedGuards === null) {
            $this->cachedGuards = User::where('user_type', 0)
                ->where('disabled', false)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get()
                ->mapWithKeys(function ($user) {
                    $name = trim("{$user->first_name} {$user->middle_name} {$user->last_name}");
                    return [$user->id => $name];
                });
        }
        return $this->cachedGuards;
    }
    
    // Get guards for filtering (includes disabled guards) - cached User collection
    private function getFilterGuardsCollection()
    {
        if ($this->cachedFilterGuardsCollection === null) {
            $this->cachedFilterGuardsCollection = User::where('user_type', 0)
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
                return [$user->id => $name];
            });
        }
        return $this->cachedFilterGuards;
    }

    // Computed property for locations
    public function getLocationsProperty()
    {
        return $this->getCachedLocations();
    }

    // Computed property for drivers
    public function getDriversProperty()
    {
        return $this->getCachedDrivers();
    }

    // Computed property for trucks
    public function getTrucksProperty()
    {
        return $this->getCachedTrucks();
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
    
    // Computed properties for filtered filter options
    public function getFilterTruckOptionsProperty()
    {
        $trucks = $this->getCachedTrucks();
        $allOptions = $trucks->pluck('plate_number', 'id');
        $options = $allOptions;
        
        // Apply search filter
        if (!empty($this->searchFilterPlateNumber)) {
            $searchTerm = strtolower($this->searchFilterPlateNumber);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected values are always included
            $options = $this->ensureSelectedInOptions($options, $this->filterPlateNumber, $allOptions);
        }
        
        return $options->toArray();
    }
    
    public function getFilterDriverOptionsProperty()
    {
        $drivers = $this->getCachedDrivers();
        $allOptions = $drivers->pluck('full_name', 'id');
        $options = $allOptions;
        
        // Apply search filter
        if (!empty($this->searchFilterDriver)) {
            $searchTerm = strtolower($this->searchFilterDriver);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected values are always included
            $options = $this->ensureSelectedInOptions($options, $this->filterDriver, $allOptions);
        }
        
        return $options->toArray();
    }
    
    public function getFilterHatcheryGuardOptionsProperty()
    {
        // Always use the cached guards - no need to cache filtered options as they change frequently
        $guards = $this->getFilterGuards();
        $allOptions = $guards;
        $options = $allOptions;
        
        // Apply search filter
        if (!empty($this->searchFilterHatcheryGuard)) {
            $searchTerm = strtolower($this->searchFilterHatcheryGuard);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected values are always included
            $options = $this->ensureSelectedInOptions($options, $this->filterHatcheryGuard, $allOptions);
        }
        
        return is_array($options) ? $options : $options->toArray();
    }
    
    public function getFilterReceivedGuardOptionsProperty()
    {
        // Always use the cached guards - no need to cache filtered options as they change frequently
        $guards = $this->getFilterGuards();
        $allOptions = $guards;
        $options = $allOptions;
        
        // Apply search filter
        if (!empty($this->searchFilterReceivedGuard)) {
            $searchTerm = strtolower($this->searchFilterReceivedGuard);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected values are always included
            $options = $this->ensureSelectedInOptions($options, $this->filterReceivedGuard, $allOptions);
        }
        
        return is_array($options) ? $options : $options->toArray();
    }
    
    public function getFilterOriginOptionsProperty()
    {
        $locations = $this->getCachedLocations();
        $allOptions = $locations->pluck('location_name', 'id');
        $options = $allOptions;
        
        // Apply search filter
        if (!empty($this->searchFilterOrigin)) {
            $searchTerm = strtolower($this->searchFilterOrigin);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected values are always included
            $options = $this->ensureSelectedInOptions($options, $this->filterOrigin, $allOptions);
        }
        
        return $options->toArray();
    }
    
    public function getFilterDestinationOptionsProperty()
    {
        $locations = $this->getCachedLocations();
        $allOptions = $locations->pluck('location_name', 'id');
        $options = $allOptions;
        
        // Apply search filter
        if (!empty($this->searchFilterDestination)) {
            $searchTerm = strtolower($this->searchFilterDestination);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected values are always included
            $options = $this->ensureSelectedInOptions($options, $this->filterDestination, $allOptions);
        }
        
        return $options->toArray();
    }

    // Computed property for guards (users)
    public function getGuardsProperty()
    {
        // Return all guards (including disabled) for filter display purposes
        return $this->getFilterGuardsCollection()->keyBy('id');
    }

    // Computed property for available origins (excludes selected destination)
    public function getAvailableOriginsProperty()
    {
        $locations = $this->getCachedLocations();
        
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
        $locations = $this->getCachedLocations();
        
        if ($this->location_id) {
            return $locations->where('id', '!=', $this->location_id)
                ->pluck('location_name', 'id')
                ->toArray();
        }
        
        return $locations->pluck('location_name', 'id')->toArray();
    }
    
    // Computed properties for create modal filtered options
    public function getCreateTruckOptionsProperty()
    {
        $trucks = $this->getCachedTrucks();
        $allOptions = $trucks->pluck('plate_number', 'id');
        $options = $allOptions;
        
        if (!empty($this->searchTruck)) {
            $searchTerm = strtolower($this->searchTruck);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected value is always included
            $options = $this->ensureSelectedInOptions($options, $this->truck_id, $allOptions);
        }
        
        return $options->toArray();
    }
    
    public function getCreateDriverOptionsProperty()
    {
        $drivers = $this->getCachedDrivers();
        $allOptions = $drivers->pluck('full_name', 'id');
        $options = $allOptions;
        
        if (!empty($this->searchDriver)) {
            $searchTerm = strtolower($this->searchDriver);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected value is always included
            $options = $this->ensureSelectedInOptions($options, $this->driver_id, $allOptions);
        }
        
        return $options->toArray();
    }
    
    public function getCreateGuardOptionsProperty()
    {
        $guards = $this->getCachedGuards();
        $allOptions = $guards;
        
        // Exclude receiving guard from hatchery guard options
        if ($this->received_guard_id) {
            $guards = $guards->filter(function ($value, $key) {
                return $key != $this->received_guard_id;
            });
        }
        
        if (!empty($this->searchHatcheryGuard)) {
            $searchTerm = strtolower($this->searchHatcheryGuard);
            $guards = $guards->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected value is always included (if it's not the receiving guard)
            if ($this->hatchery_guard_id && $this->hatchery_guard_id != $this->received_guard_id) {
                $guards = $this->ensureSelectedInOptions($guards, $this->hatchery_guard_id, $allOptions);
            }
        }
        
        return $guards->toArray();
    }
    
    public function getCreateReceivedGuardOptionsProperty()
    {
        $guards = $this->getCachedGuards();
        $allOptions = $guards;
        
        // Exclude hatchery guard from receiving guard options
        if ($this->hatchery_guard_id) {
            $guards = $guards->filter(function ($value, $key) {
                return $key != $this->hatchery_guard_id;
            });
        }
        
        if (!empty($this->searchReceivedGuard)) {
            $searchTerm = strtolower($this->searchReceivedGuard);
            $guards = $guards->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected value is always included (if it's not the hatchery guard)
            if ($this->received_guard_id && $this->received_guard_id != $this->hatchery_guard_id) {
                $guards = $this->ensureSelectedInOptions($guards, $this->received_guard_id, $allOptions);
            }
        }
        
        return $guards->toArray();
    }
    
    // Computed properties for details modal filtered options
    public function getDetailsTruckOptionsProperty()
    {
        $trucks = $this->getCachedTrucks();
        $allOptions = $trucks->pluck('plate_number', 'id');
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
        $locations = $this->getCachedLocations();
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
        $drivers = $this->getCachedDrivers();
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
    
    // Computed properties for edit modal filtered options
    public function getEditTruckOptionsProperty()
    {
        $trucks = $this->getCachedTrucks();
        $allOptions = $trucks->pluck('plate_number', 'id');
        
        // If selected truck is soft-deleted, include it in options
        if ($this->editTruckId) {
            $selectedTruck = Truck::withTrashed()->find($this->editTruckId);
            if ($selectedTruck && $selectedTruck->trashed() && !isset($allOptions[$this->editTruckId])) {
                $allOptions[$this->editTruckId] = $selectedTruck->plate_number . ' (Deleted)';
            }
        }
        
        $options = $allOptions;
        
        if (!empty($this->searchEditTruck)) {
            $searchTerm = strtolower($this->searchEditTruck);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected value is always included (even if soft-deleted)
            $options = $this->ensureSelectedInOptions($options, $this->editTruckId, $allOptions);
        }
        
        return $options->toArray();
    }

    public function getIsSelectedTruckSoftDeletedProperty()
    {
        if (!$this->editTruckId || !$this->selectedSlip) {
            return false;
        }
        
        $truck = Truck::withTrashed()->find($this->editTruckId);
        return $truck && $truck->trashed();
    }
    
    public function getEditDriverOptionsProperty()
    {
        $drivers = $this->getCachedDrivers();
        $allOptions = $drivers->pluck('full_name', 'id');
        $options = $allOptions;
        
        if (!empty($this->searchEditDriver)) {
            $searchTerm = strtolower($this->searchEditDriver);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected value is always included
            $options = $this->ensureSelectedInOptions($options, $this->editDriverId, $allOptions);
        }
        
        return $options->toArray();
    }
    
    public function getEditGuardOptionsProperty()
    {
        $guards = $this->getCachedGuards();
        $allOptions = $guards;
        
        // Exclude receiving guard from hatchery guard options
        if ($this->editReceivedGuardId) {
            $guards = $guards->filter(function ($value, $key) {
                return $key != $this->editReceivedGuardId;
            });
        }
        
        if (!empty($this->searchEditHatcheryGuard)) {
            $searchTerm = strtolower($this->searchEditHatcheryGuard);
            $guards = $guards->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected value is always included (if it's not the receiving guard)
            if ($this->editHatcheryGuardId && $this->editHatcheryGuardId != $this->editReceivedGuardId) {
                $guards = $this->ensureSelectedInOptions($guards, $this->editHatcheryGuardId, $allOptions);
            }
        }
        
        return $guards->toArray();
    }
    
    public function getEditReceivedGuardOptionsProperty()
    {
        $guards = $this->getCachedGuards();
        $allOptions = $guards;
        
        // Exclude hatchery guard from receiving guard options
        if ($this->editHatcheryGuardId) {
            $guards = $guards->filter(function ($value, $key) {
                return $key != $this->editHatcheryGuardId;
            });
        }
        
        if (!empty($this->searchEditReceivedGuard)) {
            $searchTerm = strtolower($this->searchEditReceivedGuard);
            $guards = $guards->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected value is always included (if it's not the hatchery guard)
            if ($this->editReceivedGuardId && $this->editReceivedGuardId != $this->editHatcheryGuardId) {
                $guards = $this->ensureSelectedInOptions($guards, $this->editReceivedGuardId, $allOptions);
            }
        }
        
        return $guards->toArray();
    }
    
    public function getEditAvailableOriginsOptionsProperty()
    {
        $locations = $this->getCachedLocations();
        
        // Exclude selected destination from origins
        $originOptions = $locations;
        if ($this->editDestinationId) {
            $originOptions = $originOptions->where('id', '!=', $this->editDestinationId);
        }
        $originOptions = $originOptions->pluck('location_name', 'id');
        
        // Apply search filter
        if (!empty($this->searchEditOrigin)) {
            $searchTerm = strtolower($this->searchEditOrigin);
            $originOptions = $originOptions->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected value is always included (if it's not the destination)
            if ($this->editLocationId && $this->editLocationId != $this->editDestinationId) {
                $allOptions = $locations->pluck('location_name', 'id');
                $originOptions = $this->ensureSelectedInOptions($originOptions, $this->editLocationId, $allOptions);
            }
        }
        
        return $originOptions->toArray();
    }
    
    public function getEditAvailableDestinationsOptionsProperty()
    {
        $locations = $this->getCachedLocations();
        
        // Exclude selected origin from destinations
        $destinationOptions = $locations;
        if ($this->editLocationId) {
            $destinationOptions = $destinationOptions->where('id', '!=', $this->editLocationId);
        }
        $destinationOptions = $destinationOptions->pluck('location_name', 'id');
        
        // Apply search filter
        if (!empty($this->searchEditDestination)) {
            $searchTerm = strtolower($this->searchEditDestination);
            $destinationOptions = $destinationOptions->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected value is always included (if it's not the origin)
            if ($this->editDestinationId && $this->editDestinationId != $this->editLocationId) {
                $allOptions = $locations->pluck('location_name', 'id');
                $destinationOptions = $this->ensureSelectedInOptions($destinationOptions, $this->editDestinationId, $allOptions);
            }
        }
        
        return $destinationOptions->toArray();
    }
    
    // Computed properties for available origins and destinations (reactive)
    public function getAvailableOriginsOptionsProperty()
    {
        $locations = $this->getCachedLocations()->whereNull('deleted_at')->where('disabled', false);

        // Exclude selected destination from origins
        $originOptions = $locations;
        if ($this->destination_id) {
            $originOptions = $originOptions->where('id', '!=', $this->destination_id);
        }
        $originOptions = $originOptions->pluck('location_name', 'id');

        // Apply search filter
        if (!empty($this->searchOrigin)) {
            $searchTerm = strtolower($this->searchOrigin);
            $originOptions = $originOptions->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected value is always included (if it's not the destination)
            if ($this->location_id && $this->location_id != $this->destination_id) {
                $allOptions = $locations->pluck('location_name', 'id');
                $originOptions = $this->ensureSelectedInOptions($originOptions, $this->location_id, $allOptions);
            }
        }

        return $originOptions->toArray();
    }
    
    public function getAvailableDestinationsOptionsProperty()
    {
        $locations = $this->getCachedLocations()->whereNull('deleted_at')->where('disabled', false);

        // Exclude selected origin from destinations
        $destinationOptions = $locations;
        if ($this->location_id) {
            $destinationOptions = $destinationOptions->where('id', '!=', $this->location_id);
        }
        $destinationOptions = $destinationOptions->pluck('location_name', 'id');

        // Apply search filter
        if (!empty($this->searchDestination)) {
            $searchTerm = strtolower($this->searchDestination);
            $destinationOptions = $destinationOptions->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected value is always included (if it's not the origin)
            if ($this->destination_id && $this->destination_id != $this->location_id) {
                $allOptions = $locations->pluck('location_name', 'id');
                $destinationOptions = $this->ensureSelectedInOptions($destinationOptions, $this->destination_id, $allOptions);
            }
        }

        return $destinationOptions->toArray();
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
        // Use filterStatus directly - it's already an integer (0, 1, 2, 3) or null
        // null = All Statuses (no filter), 0 = Pending, 1 = Disinfecting, 2 = Ongoing, 3 = Completed
        $this->appliedStatus = $this->filterStatus; // Already an int or null
        // Create new array instances to ensure Livewire detects the change
        // Convert string IDs to integers for proper filtering
        $this->appliedOrigin = array_values(array_map('intval', $this->filterOrigin ?? []));
        $this->appliedDestination = array_values(array_map('intval', $this->filterDestination ?? []));
        $this->appliedDriver = array_values(array_map('intval', $this->filterDriver ?? []));
        $this->appliedPlateNumber = array_values(array_map('intval', $this->filterPlateNumber ?? []));
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
            !empty($this->appliedPlateNumber) ||
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
            case 'plateNumber':
                $this->appliedPlateNumber = [];
                $this->filterPlateNumber = [];
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
            case 'plateNumber':
                $this->appliedPlateNumber = array_values(array_filter($this->appliedPlateNumber, function($id) use ($valueToRemove) {
                    return $id != $valueToRemove;
                }));
                $this->filterPlateNumber = $this->appliedPlateNumber;
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
            !empty($this->appliedPlateNumber) ||
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
        $this->filterPlateNumber = [];
        $this->filterHatcheryGuard = [];
        $this->filterReceivedGuard = [];
        $this->filterCreatedFrom = null;
        $this->filterCreatedTo = null;
        
        // Clear search properties
        $this->searchFilterPlateNumber = '';
        $this->searchFilterDriver = '';
        $this->searchFilterOrigin = '';
        $this->searchFilterDestination = '';
        $this->searchFilterHatcheryGuard = '';
        $this->searchFilterReceivedGuard = '';
        
        $this->appliedStatus = null;
        $this->appliedOrigin = [];
        $this->appliedDestination = [];
        $this->appliedDriver = [];
        $this->appliedPlateNumber = [];
        $this->appliedHatcheryGuard = [];
        $this->appliedReceivedGuard = [];
        $this->appliedCreatedFrom = null;
        $this->appliedCreatedTo = null;
        
        $this->filtersActive = false;
        $this->resetPage();
    }

    // ==================== DETAILS MODAL METHODS ====================

    public function openDetailsModal($id)
    {
        $this->selectedSlip = DisinfectionSlipModel::with([
            'truck' => function($q) { $q->withTrashed(); },
            'location',
            'destination',
            'driver',
            'hatcheryGuard',
            'receivedGuard'
        ])->find($id);

        $this->showDetailsModal = true;
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

        // Admin cannot edit completed slips (status == 3 or completed_at is set)
        // Only SuperAdmins can edit completed slips
        return $this->selectedSlip->status != 3 && $this->selectedSlip->completed_at === null;
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

        // Admin can delete any slip, including completed ones (unless truck is soft-deleted)
        return true;
    }

    public function canRemoveAttachment()
    {
        if (!$this->selectedSlip) {
            return false;
        }

        // Admin cannot remove attachment from completed slips (status == 3 or completed_at is set)
        // Only SuperAdmins can remove attachments from completed slips
        if ($this->selectedSlip->status == 3 || $this->selectedSlip->completed_at !== null) {
            return false;
        }

        $attachmentIds = $this->selectedSlip->attachment_ids ?? [];
        return !empty($attachmentIds);
    }

    public function openEditModal()
    {
        // Load slip data into edit fields
        $this->editTruckId = $this->selectedSlip->truck_id;
        $this->editLocationId = $this->selectedSlip->location_id;
        $this->editDestinationId = $this->selectedSlip->destination_id;
        $this->editDriverId = $this->selectedSlip->driver_id;
        $this->editHatcheryGuardId = $this->selectedSlip->hatchery_guard_id;
        $this->editReceivedGuardId = $this->selectedSlip->received_guard_id;
        $this->editReasonForDisinfection = $this->selectedSlip->reason_for_disinfection;
        $this->editStatus = $this->selectedSlip->status;
        
        // Reset search properties
        $this->searchEditTruck = '';
        $this->searchEditOrigin = '';
        $this->searchEditDestination = '';
        $this->searchEditDriver = '';
        $this->searchEditHatcheryGuard = '';
        $this->searchEditReceivedGuard = '';
        
        $this->showEditModal = true;
    }
    
    public function updatedEditStatus($value)
    {
        // Status 0, 1, 2 (Pending, Disinfecting, Ongoing): Receiving guard is optional
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
        $this->editReasonForDisinfection = null;
        $this->editStatus = null;
        $this->searchEditTruck = '';
        $this->searchEditOrigin = '';
        $this->searchEditDestination = '';
        $this->searchEditDriver = '';
        $this->searchEditHatcheryGuard = '';
        $this->searchEditReceivedGuard = '';
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
               $this->editReasonForDisinfection != $this->selectedSlip->reason_for_disinfection ||
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
        // Authorization check - Admins cannot edit completed slips
        if (!$this->canEdit()) {
            $this->dispatch('toast', message: 'You are not authorized to edit completed slips.', type: 'error');
            return;
        }

        // Use the edited status, not the current status
        $status = $this->editStatus;
        
        // Validate status
        $this->validate([
            'editStatus' => 'required|in:0,1,2,3',
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
            'editReasonForDisinfection' => 'nullable|string|max:1000',
        ];

        // Status 0, 1, 2 (Pending, Disinfecting, Ongoing): Origin and Hatchery Guard are required, Receiving Guard is optional
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
            'editTruckId' => 'Plate Number',
            'editLocationId' => 'Origin',
            'editDestinationId' => 'Destination',
            'editDriverId' => 'Driver',
            'editHatcheryGuardId' => 'Hatchery Guard',
            'editReceivedGuardId' => 'Receiving Guard',
            'editReasonForDisinfection' => 'Reason for Disinfection',
            'editStatus' => 'Status',
        ]);

        // Check if there are any changes
        if (!$this->hasEditUnsavedChanges()) {
            $this->dispatch('toast', message: 'No changes detected.', type: 'info');
            return;
        }

        // Sanitize reason_for_disinfection
        $sanitizedReason = $this->sanitizeText($this->editReasonForDisinfection);

        // Capture old values for logging
        $oldValues = $this->selectedSlip->only([
            'truck_id', 'location_id', 'destination_id', 'driver_id',
            'hatchery_guard_id', 'received_guard_id', 'reason_for_disinfection', 'status'
        ]);

        // Build update data based on status
        $updateData = [
            'truck_id' => $this->editTruckId,
            'destination_id' => $this->editDestinationId,
            'driver_id' => $this->editDriverId,
            'reason_for_disinfection' => $sanitizedReason,
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

        // Refresh the slip with relationships
        $this->selectedSlip->refresh();
        $this->selectedSlip->load([
            'truck',
            'location',
            'destination',
            'driver',
            'hatcheryGuard',
            'receivedGuard'
        ]);

        $slipId = $this->selectedSlip->slip_id;
        
        // Log the update
        $newValues = $this->selectedSlip->only([
            'truck_id', 'location_id', 'destination_id', 'driver_id',
            'hatchery_guard_id', 'received_guard_id', 'reason_for_disinfection', 'status'
        ]);
        Logger::update(
            DisinfectionSlipModel::class,
            $this->selectedSlip->id,
            "Updated slip {$slipId}",
            $oldValues,
            $newValues
        );
        
        $this->resetEditForm();
        $this->showEditModal = false;
        $this->dispatch('toast', message: "{$slipId} has been updated.", type: 'success');
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
            'truck_id', 'location_id', 'destination_id', 'driver_id',
            'hatchery_guard_id', 'received_guard_id', 'reason_for_disinfection', 'status'
        ]);
        
        // Atomic delete: Only delete if not already deleted to prevent race conditions
        $deleted = DisinfectionSlipModel::where('id', $this->selectedSlip->id)
            ->whereNull('deleted_at') // Only delete if not already deleted
            ->update(['deleted_at' => now()]);
        
        if ($deleted === 0) {
            // Slip was already deleted by another process
            $this->showDeleteConfirmation = false;
            $this->dispatch('toast', message: 'This slip was already deleted by another administrator. Please refresh the page.', type: 'error');
            return;
        }
        
        // Log the delete
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

    public function closeDetailsModal()
    {
        $this->showDeleteConfirmation = false;
        $this->showRemoveAttachmentConfirmation = false;
        $this->showDetailsModal = false;
        $this->js('setTimeout(() => $wire.clearSelectedSlip(), 300)');
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
        $this->reason_for_disinfection = null;
        $this->searchOrigin = '';
        $this->searchDestination = '';
        $this->searchTruck = '';
        $this->searchDriver = '';
        $this->searchHatcheryGuard = '';
        $this->searchReceivedGuard = '';
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
               !empty($this->reason_for_disinfection);
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
            'reason_for_disinfection' => 'nullable|string|max:1000',
        ], [], [
            'location_id' => 'Origin',
            'destination_id' => 'Destination',
            'truck_id' => 'Truck',
            'driver_id' => 'Driver',
            'hatchery_guard_id' => 'Hatchery Guard',
            'received_guard_id' => 'Receiving Guard',
            'reason_for_disinfection' => 'Reason for Disinfection',
        ]);

        // Sanitize reason_for_disinfection
        $sanitizedReason = $this->sanitizeText($this->reason_for_disinfection);

        $slip = DisinfectionSlipModel::create([
            'truck_id' => $this->truck_id,
            'location_id' => $this->location_id,
            'destination_id' => $this->destination_id,
            'driver_id' => $this->driver_id,
            'hatchery_guard_id' => $this->hatchery_guard_id,
            'received_guard_id' => $this->received_guard_id,
            'reason_for_disinfection' => $sanitizedReason,
            'status' => 0, // Pending
        ]);

        $slipId = $slip->slip_id;
        
        // Log the creation
        $newValues = $slip->only([
            'truck_id', 'location_id', 'destination_id', 'driver_id',
            'hatchery_guard_id', 'received_guard_id', 'reason_for_disinfection', 'status'
        ]);
        Logger::create(
            DisinfectionSlipModel::class,
            $slip->id,
            "Created slip {$slipId}",
            $newValues
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
        // Clear search when selection changes to show all options
        $this->searchOrigin = '';
        $this->searchDestination = '';
    }

    public function updatedDestinationId()
    {
        // If origin is the same as destination, clear it
        if ($this->location_id == $this->destination_id) {
            $this->location_id = null;
        }
        // Clear search when selection changes to show all options
        $this->searchOrigin = '';
        $this->searchDestination = '';
    }

    public function updatedHatcheryGuardId()
    {
        // If receiving guard is the same as hatchery guard, clear it
        if ($this->received_guard_id == $this->hatchery_guard_id) {
            $this->received_guard_id = null;
        }
        // Clear search when selection changes
        $this->searchHatcheryGuard = '';
        $this->searchReceivedGuard = '';
    }

    public function updatedReceivedGuardId()
    {
        // If receiving guard is set to hatchery guard, clear the hatchery guard
        if ($this->received_guard_id == $this->hatchery_guard_id) {
            $this->hatchery_guard_id = null;
        }
        // Clear search when selection changes
        $this->searchReceivedGuard = '';
    }

    public function updatedEditLocationId()
    {
        // If destination is the same as origin, clear it
        if ($this->editDestinationId == $this->editLocationId) {
            $this->editDestinationId = null;
        }
        // Clear search when selection changes to show all options
        $this->searchEditOrigin = '';
        $this->searchEditDestination = '';
    }

    public function updatedEditDestinationId()
    {
        // If origin is the same as destination, clear it
        if ($this->editLocationId == $this->editDestinationId) {
            $this->editLocationId = null;
        }
        // Clear search when selection changes to show all options
        $this->searchEditOrigin = '';
        $this->searchEditDestination = '';
    }

    public function updatedEditHatcheryGuardId()
    {
        // If receiving guard is the same as hatchery guard, clear it
        if ($this->editReceivedGuardId == $this->editHatcheryGuardId) {
            $this->editReceivedGuardId = null;
        }
        // Clear search when selection changes
        $this->searchEditHatcheryGuard = '';
        $this->searchEditReceivedGuard = '';
    }

    public function updatedEditReceivedGuardId()
    {
        // If receiving guard is set to hatchery guard, clear the hatchery guard
        if ($this->editReceivedGuardId == $this->editHatcheryGuardId) {
            $this->editHatcheryGuardId = null;
        }
        // Clear search when selection changes
        $this->searchEditReceivedGuard = '';
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
    }

    public function nextAttachment()
    {
        $attachments = $this->selectedSlip->attachments();
        if ($this->currentAttachmentIndex < $attachments->count() - 1) {
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
                $this->dispatch('toast', message: 'Cannot remove attachment from a completed slip.', type: 'error');
                return;
            }

            if (!$this->attachmentToDelete) {
                $this->dispatch('toast', message: 'No attachment specified to remove.', type: 'error');
                return;
            }

            // Get current attachment IDs
            $attachmentIds = $this->selectedSlip->attachment_ids ?? [];
            
            if (empty($attachmentIds) || !in_array($this->attachmentToDelete, $attachmentIds)) {
                $this->dispatch('toast', message: 'Attachment not found.', type: 'error');
                return;
            }

            // Get the attachment record
            $attachment = Attachment::find($this->attachmentToDelete);

            if ($attachment) {
                // Delete the physical file from storage (except BGC.png logo)
                if ($attachment->file_path !== 'images/logo/BGC.png') {
                    if (Storage::disk('public')->exists($attachment->file_path)) {
                        Storage::disk('public')->delete($attachment->file_path);
                    }
                }

                // Remove attachment ID from array
                $attachmentIds = array_values(array_filter($attachmentIds, fn($id) => $id != $this->attachmentToDelete));

                // Update slip with remaining attachment IDs (or null if empty)
                $this->selectedSlip->update([
                    'attachment_ids' => empty($attachmentIds) ? null : $attachmentIds,
                ]);

                // Hard delete the attachment record (except BGC.png logo)
                if ($attachment->file_path !== 'images/logo/BGC.png') {
                    $attachment->forceDelete();
                }
            }

            // Refresh the slip
            $this->selectedSlip->refresh();

            // Adjust current index if needed
            $attachments = $this->selectedSlip->attachments();
            if ($this->currentAttachmentIndex >= $attachments->count() && $attachments->count() > 0) {
                $this->currentAttachmentIndex = $attachments->count() - 1;
            } elseif ($attachments->count() === 0) {
                // No more attachments, close modal
                $this->showAttachmentModal = false;
                $this->currentAttachmentIndex = 0;
            }

            // Close confirmation modal
            $this->showRemoveAttachmentConfirmation = false;
            $this->attachmentToDelete = null;

            $slipId = $this->selectedSlip->slip_id;
            $this->dispatch('toast', message: "Attachment has been removed from {$slipId}.", type: 'success');

        } catch (\Exception $e) {
            FacadesLog::error('Attachment removal error: ' . $e->getMessage());
            $this->dispatch('toast', message: 'Failed to remove attachment. Please try again.', type: 'error');
        }
    }

    /**
     * Sanitize text input (for textarea fields like reason_for_disinfection)
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
        $slips = DisinfectionSlipModel::with(['truck', 'location', 'destination', 'driver', 'hatcheryGuard', 'receivedGuard'])
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
                            $truckQuery->where('plate_number', 'like', '%' . $escapedSearchTerm . '%');
                        })
                        ->orWhereHas('driver', function($driverQuery) use ($escapedSearchTerm) {
                            $driverQuery->where('first_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhere('last_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%'])
                                ->orWhereRaw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%']);
                        })
                        ->orWhereHas('location', function($locationQuery) use ($escapedSearchTerm) {
                            $locationQuery->where('location_name', 'like', '%' . $escapedSearchTerm . '%');
                        })
                        ->orWhereHas('destination', function($destinationQuery) use ($escapedSearchTerm) {
                            $destinationQuery->where('location_name', 'like', '%' . $escapedSearchTerm . '%');
                        })
                        ->orWhereHas('hatcheryGuard', function($guardQuery) use ($escapedSearchTerm) {
                            $guardQuery->where('first_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhere('middle_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhere('last_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%'])
                                ->orWhereRaw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%']);
                        })
                        ->orWhereHas('receivedGuard', function($guardQuery) use ($escapedSearchTerm) {
                            $guardQuery->where('first_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhere('middle_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhere('last_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%'])
                                ->orWhereRaw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%']);
                        });
                });
            })
            // Status filter
            // Important: Check for null explicitly, as 0 is a valid status (Pending)
            ->when($this->filtersActive && $this->appliedStatus !== null, function($query) {
                // appliedStatus is already an integer (0, 1, 2, or 3)
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
            // Plate number filter
            ->when($this->filtersActive && !empty($this->appliedPlateNumber), function($query) {
                $query->whereIn('truck_id', $this->appliedPlateNumber);
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
            // Apply sorting (works with all filters)
            ->when($this->sortBy === 'slip_id', function($query) {
                // For slip_id format "YY-00001", extract the numeric part (starts at position 4, after "YY-")
                // Sort by year first, then by number within that year
                $direction = strtoupper($this->sortDirection);
                $query->orderByRaw("SUBSTRING(slip_id, 1, 2) " . $direction) // Year part
                      ->orderByRaw("CAST(SUBSTRING(slip_id, 4) AS UNSIGNED) " . $direction); // Number part
            })
            ->when($this->sortBy !== 'slip_id', function($query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
                // Add secondary sort by slip_id for consistent ordering when primary sort values are equal
                $direction = strtoupper($this->sortDirection);
                $query->orderByRaw("SUBSTRING(slip_id, 1, 2) " . $direction) // Year part
                      ->orderByRaw("CAST(SUBSTRING(slip_id, 4) AS UNSIGNED) " . $direction); // Number part
            })
            ->paginate(10);

        return view('livewire.admin.trucks', [
            'slips' => $slips,
            'locations' => $this->locations,
            'drivers' => $this->drivers,
            'trucks' => $this->trucks,
            'guards' => $this->guards,
            'availableOriginsOptions' => $this->availableOriginsOptions,
            'availableDestinationsOptions' => $this->availableDestinationsOptions,
            'availableStatuses' => $this->availableStatuses,
            'filterTruckOptions' => $this->filterTruckOptions,
            'filterDriverOptions' => $this->filterDriverOptions,
            'filterHatcheryGuardOptions' => $this->filterHatcheryGuardOptions,
            'filterReceivedGuardOptions' => $this->filterReceivedGuardOptions,
            'filterOriginOptions' => $this->filterOriginOptions,
            'filterDestinationOptions' => $this->filterDestinationOptions,
            'createTruckOptions' => $this->createTruckOptions,
            'createDriverOptions' => $this->createDriverOptions,
            'createGuardOptions' => $this->createGuardOptions,
            'createReceivedGuardOptions' => $this->createReceivedGuardOptions,
            'detailsTruckOptions' => $this->detailsTruckOptions,
            'detailsLocationOptions' => $this->detailsLocationOptions,
            'detailsDriverOptions' => $this->detailsDriverOptions,
            'editTruckOptions' => $this->editTruckOptions,
            'editDriverOptions' => $this->editDriverOptions,
            'editGuardOptions' => $this->editGuardOptions,
            'editReceivedGuardOptions' => $this->editReceivedGuardOptions,
            'editAvailableOriginsOptions' => $this->editAvailableOriginsOptions,
            'editAvailableDestinationsOptions' => $this->editAvailableDestinationsOptions,
        ]);
    }

    public function getExportData()
    {
        return DisinfectionSlipModel::with(['truck', 'location', 'destination', 'driver', 'hatcheryGuard', 'receivedGuard'])
            ->when($this->search, function ($query) {
                $searchTerm = trim($this->search);
                $searchTerm = preg_replace('/[%_]/', '', $searchTerm);
                if (empty($searchTerm)) {
                    return;
                }
                $escapedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                $query->where(function($q) use ($escapedSearchTerm) {
                    $q->where('slip_id', 'like', '%' . $escapedSearchTerm . '%')
                        ->orWhereHas('truck', function($truckQuery) use ($escapedSearchTerm) {
                            $truckQuery->where('plate_number', 'like', '%' . $escapedSearchTerm . '%');
                        })
                        ->orWhereHas('driver', function($driverQuery) use ($escapedSearchTerm) {
                            $driverQuery->where('first_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhere('middle_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhere('last_name', 'like', '%' . $escapedSearchTerm . '%')
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%'])
                                ->orWhereRaw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?", ['%' . $escapedSearchTerm . '%']);
                        })
                        ->orWhereHas('location', function($locationQuery) use ($escapedSearchTerm) {
                            $locationQuery->where('location_name', 'like', '%' . $escapedSearchTerm . '%');
                        })
                        ->orWhereHas('destination', function($destinationQuery) use ($escapedSearchTerm) {
                            $destinationQuery->where('location_name', 'like', '%' . $escapedSearchTerm . '%');
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
            ->when(!empty($this->appliedPlateNumber), function($query) {
                $query->whereIn('truck_id', $this->appliedPlateNumber);
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
            ->when($this->sortBy === 'slip_id', function($query) {
                $direction = strtoupper($this->sortDirection);
                $query->orderByRaw("SUBSTRING(slip_id, 1, 2) " . $direction)
                      ->orderByRaw("CAST(SUBSTRING(slip_id, 4) AS UNSIGNED) " . $direction);
            })
            ->when($this->sortBy !== 'slip_id', function($query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            })
            ->get();
    }

    public function exportCSV()
    {
        $data = $this->getExportData();
        $filename = 'disinfection_slips_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['Slip ID', 'Plate Number', 'Origin', 'Destination', 'Driver', 'Status', 'Hatchery Guard', 'Received Guard', 'Created Date', 'Completed Date']);
            
            foreach ($data as $slip) {
                $statuses = ['Pending', 'Disinfecting', 'In-Transit', 'Completed'];
                $status = $statuses[$slip->status] ?? 'Unknown';
                $hatcheryGuard = $slip->hatcheryGuard ? trim(implode(' ', array_filter([$slip->hatcheryGuard->first_name, $slip->hatcheryGuard->middle_name, $slip->hatcheryGuard->last_name]))) : 'N/A';
                $receivedGuard = $slip->receivedGuard ? trim(implode(' ', array_filter([$slip->receivedGuard->first_name, $slip->receivedGuard->middle_name, $slip->receivedGuard->last_name]))) : 'N/A';
                $driver = $slip->driver ? trim(implode(' ', array_filter([$slip->driver->first_name, $slip->driver->middle_name, $slip->driver->last_name]))) : 'N/A';
                
                fputcsv($file, [
                    $slip->slip_id,
                    $slip->truck->plate_number ?? 'N/A',
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
        $data = $this->getExportData();
        $exportData = $data->map(function($slip) {
            return [
                'slip_id' => $slip->slip_id,
                'plate_number' => $slip->truck->plate_number ?? 'N/A',
                'origin' => $slip->location->location_name ?? 'N/A',
                'destination' => $slip->destination->location_name ?? 'N/A',
                'driver' => $slip->driver ? trim(implode(' ', array_filter([$slip->driver->first_name, $slip->driver->middle_name, $slip->driver->last_name]))) : 'N/A',
                'status' => $slip->status,
                'hatchery_guard' => $slip->hatcheryGuard ? trim(implode(' ', array_filter([$slip->hatcheryGuard->first_name, $slip->hatcheryGuard->middle_name, $slip->hatcheryGuard->last_name]))) : 'N/A',
                'received_guard' => $slip->receivedGuard ? trim(implode(' ', array_filter([$slip->receivedGuard->first_name, $slip->receivedGuard->middle_name, $slip->receivedGuard->last_name]))) : 'N/A',
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
            'plate_number' => $this->appliedPlateNumber,
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
        
        $printUrl = route('admin.print.trucks', ['token' => $token]);
        
        $this->dispatch('open-print-window', ['url' => $printUrl]);
    }

    public function printSlip($slipId)
    {
        $token = Str::random(32);
        Session::put("print_slip_{$token}", $slipId);
        Session::put("print_slip_{$token}_expires", now()->addMinutes(10));
        
        $printUrl = route('admin.print.slip', ['token' => $token]);
        
        $this->dispatch('open-print-window', ['url' => $printUrl]);
    }
}