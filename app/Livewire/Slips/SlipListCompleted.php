<?php

namespace App\Livewire\Vehicles;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Renderless;
use App\Models\DisinfectionSlip;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\Driver;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
class SlipListCompleted extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';
    
    public $search = '';
    public $showFilters = false;
    
    // Filter properties
    public $filterDestination = [];
    public $filterDriver = [];
    public $filterVehicle = [];
    public $filterCompletedFrom = '';
    public $filterCompletedTo = '';
    public $filterStatus = 'all';

    // Applied filters
    public $appliedDestination = [];
    public $appliedDriver = [];
    public $appliedVehicle = [];
    public $appliedCompletedFrom = null;
    public $appliedCompletedTo = null;
    public $appliedStatus = 'all';

    public $filtersActive = false;
    public $sortDirection = null; // null, 'asc', 'desc' (applied)
    public $filterSortDirection = null; // null, 'asc', 'desc' (temporary, in filter modal)
    
    // Search properties for filter dropdowns
    public $searchFilterVehicle = '';
    public $searchFilterDriver = '';
    public $searchFilterDestination = '';

    public function mount()
    {
        $this->filterSortDirection = $this->sortDirection; // Initialize filter sort with current sort
        $this->appliedStatus = $this->appliedStatus ?: 'all'; // Ensure appliedStatus defaults to 'all'
        $this->checkFiltersActive();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }
    
    // Lazy-load properties - only load what's in active filters
    public function getLocationsProperty()
    {
        $locationIds = $this->appliedDestination ?? [];
        
        if (empty($locationIds)) {
            return collect();
        }
        
        return Location::withTrashed()
            ->whereIn('id', $locationIds)
            ->select('id', 'location_name', 'disabled', 'deleted_at')
            ->get()
            ->keyBy('id');
    }

    public function getDriversProperty()
    {
        $driverIds = $this->appliedDriver ?? [];
        
        if (empty($driverIds)) {
            return collect();
        }
        
        return Driver::withTrashed()
            ->whereIn('id', $driverIds)
            ->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')
            ->get()
            ->keyBy('id');
    }

    public function getVehiclesProperty()
    {
        $vehicleIds = $this->appliedVehicle ?? [];
        
        if (empty($vehicleIds)) {
            return collect();
        }
        
        return Vehicle::withTrashed()
            ->whereIn('id', $vehicleIds)
            ->select('id', 'vehicle', 'disabled', 'deleted_at')
            ->get()
            ->keyBy('id');
    }

    // NOTE: Old filter options properties removed - now using paginated dropdowns
    
    // Paginated data fetching methods for searchable dropdowns
    #[Renderless]
    public function getPaginatedVehicles($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = Vehicle::query()
            ->whereNull('deleted_at')
            ->where('disabled', false)
            ->select(['id', 'vehicle']);

        if (!empty($search)) {
            $query->where('vehicle', 'like', '%' . $search . '%');
        }

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

        $query->orderBy('vehicle', 'asc');
        $offset = ($page - 1) * $perPage;
        $total = $query->count();
        $results = $query->skip($offset)->take($perPage)->get();
        $data = $results->pluck('vehicle', 'id')->toArray();
        
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

        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('first_name', 'like', $searchTerm)
                  ->orWhere('middle_name', 'like', $searchTerm)
                  ->orWhere('last_name', 'like', $searchTerm);
            });
        }

        if (!empty($includeIds)) {
            $includedItems = Driver::whereIn('id', $includeIds)
                ->select(['id', 'first_name', 'middle_name', 'last_name'])
                ->orderBy('first_name', 'asc')
                ->orderBy('last_name', 'asc')
                ->get()
                ->mapWithKeys(function($driver) {
                    return [$driver->id => trim("{$driver->first_name} {$driver->middle_name} {$driver->last_name}")];
                })
                ->toArray();
            return [
                'data' => $includedItems,
                'has_more' => false,
                'total' => count($includedItems),
            ];
        }

        $query->orderBy('first_name', 'asc')->orderBy('last_name', 'asc');
        $offset = ($page - 1) * $perPage;
        $total = $query->count();
        $results = $query->skip($offset)->take($perPage)->get();
        
        $data = $results->mapWithKeys(function($driver) {
            return [$driver->id => trim("{$driver->first_name} {$driver->middle_name} {$driver->last_name}")];
        })->toArray();
        
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

        if (!empty($search)) {
            $query->where('location_name', 'like', '%' . $search . '%');
        }

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

        $query->orderBy('location_name', 'asc');
        $offset = ($page - 1) * $perPage;
        $total = $query->count();
        $results = $query->skip($offset)->take($perPage)->get();
        $data = $results->pluck('location_name', 'id')->toArray();
        
        return [
            'data' => $data,
            'has_more' => ($offset + $perPage) < $total,
            'total' => $total,
        ];
    }
    
    
    public function applyFilters()
    {
        $this->appliedDestination = $this->filterDestination;
        $this->appliedDriver = $this->filterDriver;
        $this->appliedVehicle = $this->filterVehicle;
        $this->appliedCompletedFrom = $this->filterCompletedFrom;
        $this->appliedCompletedTo = $this->filterCompletedTo;
        $this->appliedStatus = $this->filterStatus ?: 'all';
        $this->sortDirection = $this->filterSortDirection;
        
        $this->checkFiltersActive();
        $this->resetPage();
        $this->showFilters = false;
    }
    
    public function clearFilters()
    {
        $this->filterDestination = [];
        $this->filterDriver = [];
        $this->filterVehicle = [];
        $this->filterCompletedFrom = '';
        $this->filterCompletedTo = '';
        $this->filterStatus = 'all';
        $this->filterSortDirection = null;
        
        $this->appliedDestination = [];
        $this->appliedDriver = [];
        $this->appliedVehicle = [];
        $this->appliedCompletedFrom = null;
        $this->appliedCompletedTo = null;
        $this->appliedStatus = 'all';
        $this->sortDirection = null;
        
        $this->checkFiltersActive();
        $this->resetPage();
    }
    
    public function removeFilter($filterName)
    {
        switch ($filterName) {
            case 'destination':
                $this->filterDestination = [];
                $this->appliedDestination = [];
                break;
            case 'driver':
                $this->filterDriver = [];
                $this->appliedDriver = [];
                break;
            case 'vehicle':
                $this->filterVehicle = [];
                $this->appliedVehicle = [];
                break;
            case 'completedFrom':
                $this->filterCompletedFrom = '';
                $this->appliedCompletedFrom = null;
                break;
            case 'completedTo':
                $this->filterCompletedTo = '';
                $this->appliedCompletedTo = null;
                break;
        }

        $this->checkFiltersActive();
        $this->resetPage();
    }
    
    public function removeSpecificFilter($filterName, $value)
    {
        switch ($filterName) {
            case 'destination':
                $this->filterDestination = array_values(array_filter($this->filterDestination, fn($id) => $id != $value));
                $this->appliedDestination = array_values(array_filter($this->appliedDestination, fn($id) => $id != $value));
                break;
            case 'driver':
                $this->filterDriver = array_values(array_filter($this->filterDriver, fn($id) => $id != $value));
                $this->appliedDriver = array_values(array_filter($this->appliedDriver, fn($id) => $id != $value));
                break;
            case 'vehicle':
                $this->filterVehicle = array_values(array_filter($this->filterVehicle, fn($id) => $id != $value));
                $this->appliedVehicle = array_values(array_filter($this->appliedVehicle, fn($id) => $id != $value));
                break;
        }
        
        $this->checkFiltersActive();
        $this->resetPage();
    }
    
    private function checkFiltersActive()
    {
        $this->filtersActive = !empty($this->appliedDestination) ||
                              !empty($this->appliedDriver) ||
                              !empty($this->appliedVehicle) ||
                              !empty($this->appliedCompletedFrom) ||
                              !empty($this->appliedCompletedTo) ||
                              ($this->appliedStatus !== 'all' && $this->appliedStatus !== null) ||
                              ($this->sortDirection !== null && $this->sortDirection !== 'desc');
    }
    
    public function render()
    {
        // Ensure appliedStatus is properly initialized
        if (!$this->appliedStatus || $this->appliedStatus === []) {
            $this->appliedStatus = 'all';
        }

        $location = Session::get('location_id');

        // Optimize relationship loading by only selecting needed fields
        // This significantly reduces memory usage with large datasets (5,000+ records)
        $query = DisinfectionSlip::with([
            'vehicle' => function($q) {
                $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed();
            },
            'location' => function($q) {
                $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed();
            },
            'destination' => function($q) {
                $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed();
            },
            'driver' => function($q) {
                $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed();
            },
            'hatcheryGuard' => function($q) {
                $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed();
            },
            'receivedGuard' => function($q) {
                $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed();
            }
        ])
            // COMPLETED & INCOMPLETE - Show slips based on user permissions
            ->whereIn('status', [3, 4])
            ->where(function($query) use ($location) {
                // Outgoing: show if created by current user
                $query->where(function($q) use ($location) {
                    $q->where('location_id', $location)
                      ->where('hatchery_guard_id', Auth::id());
                })
                // Incoming: show if received by current user (for both completed and incomplete slips)
                ->orWhere(function($q) use ($location) {
                    $q->where('destination_id', $location)
                      ->where('received_guard_id', Auth::id());
                });
            })
            
            // SEARCH
            ->when($this->search, function($q) {
                $q->where('slip_id', 'like', '%' . $this->search . '%');
            })
            
            // FILTERS
            ->when(!empty($this->appliedDestination), function($q) {
                $q->whereIn('destination_id', $this->appliedDestination);
            })
            ->when(!empty($this->appliedDriver), function($q) {
                $q->whereIn('driver_id', $this->appliedDriver);
            })
            ->when(!empty($this->appliedVehicle), function($q) {
                $q->whereIn('vehicle_id', $this->appliedVehicle);
            })
            ->when($this->appliedCompletedFrom, function($q) {
                $q->whereDate('completed_at', '>=', $this->appliedCompletedFrom);
            })
            ->when($this->appliedCompletedTo, function($q) {
                $q->whereDate('completed_at', '<=', $this->appliedCompletedTo);
            })
            ->when(in_array($this->appliedStatus, ['completed', 'incomplete']), function($q) {
                $statusValue = $this->appliedStatus === 'completed' ? 3 : 4;
                $q->where('status', $statusValue);
            })

            // SORT
            ->when($this->sortDirection === 'asc', function($q) {
                $q->orderBy('completed_at', 'asc');
            })
            ->when($this->sortDirection === 'desc', function($q) {
                $q->orderBy('completed_at', 'desc');
            })
            ->when($this->sortDirection === null, function($q) {
                $q->orderBy('completed_at', 'desc'); // default
            });

        $slips = $query->paginate(10);

        return view('livewire.vehicles.vehicle-list-completed', [
            'slips' => $slips
        ]);
    }
}