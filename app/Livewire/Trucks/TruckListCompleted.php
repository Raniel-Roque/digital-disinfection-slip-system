<?php

namespace App\Livewire\Trucks;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\DisinfectionSlip;
use App\Models\Truck;
use App\Models\Location;
use App\Models\Driver;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class TruckListCompleted extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';
    
    public $search = '';
    public $showFilters = false;
    
    // Filter properties
    public $filterDestination = [];
    public $filterDriver = [];
    public $filterPlateNumber = [];
    public $filterCompletedFrom = '';
    public $filterCompletedTo = '';
    
    // Applied filters
    public $appliedDestination = [];
    public $appliedDriver = [];
    public $appliedPlateNumber = [];
    public $appliedCompletedFrom = null;
    public $appliedCompletedTo = null;
    
    public $filtersActive = false;
    public $sortDirection = null; // null, 'asc', 'desc' (applied)
    public $filterSortDirection = null; // null, 'asc', 'desc' (temporary, in filter modal)
    
    // Search properties for filter dropdowns
    public $searchFilterPlateNumber = '';
    public $searchFilterDriver = '';
    public $searchFilterDestination = '';

    public function mount()
    {
        $this->filterSortDirection = $this->sortDirection; // Initialize filter sort with current sort
        $this->checkFiltersActive();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }
    
    // Computed properties for locations, drivers, trucks, guards
    public function getLocationsProperty()
    {
        return Location::withTrashed()->get();
    }

    public function getDriversProperty()
    {
        return Driver::withTrashed()->get();
    }

    public function getTrucksProperty()
    {
        return Truck::withTrashed()->get();
    }

    
    // Computed properties for filtered options
    public function getFilterTruckOptionsProperty()
    {
        $trucks = $this->trucks;
        $allOptions = $trucks->pluck('plate_number', 'id');
        $options = $allOptions;
        
        if (!empty($this->searchFilterPlateNumber)) {
            $searchTerm = strtolower($this->searchFilterPlateNumber);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
        }
        
        return $options->toArray();
    }
    
    public function getFilterDriverOptionsProperty()
    {
        $drivers = $this->drivers;
        $allOptions = $drivers->pluck('full_name', 'id');
        $options = $allOptions;
        
        if (!empty($this->searchFilterDriver)) {
            $searchTerm = strtolower($this->searchFilterDriver);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
        }
        
        return $options->toArray();
    }
    
    public function getFilterDestinationOptionsProperty()
    {
        $locations = $this->locations;
        $allOptions = $locations->pluck('location_name', 'id');
        $options = $allOptions;
        
        if (!empty($this->searchFilterDestination)) {
            $searchTerm = strtolower($this->searchFilterDestination);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
        }
        
        return $options->toArray();
    }
    
    
    public function applyFilters()
    {
        $this->appliedDestination = $this->filterDestination;
        $this->appliedDriver = $this->filterDriver;
        $this->appliedPlateNumber = $this->filterPlateNumber;
        $this->appliedCompletedFrom = $this->filterCompletedFrom;
        $this->appliedCompletedTo = $this->filterCompletedTo;
        $this->sortDirection = $this->filterSortDirection;
        
        $this->checkFiltersActive();
        $this->resetPage();
        $this->showFilters = false;
    }
    
    public function clearFilters()
    {
        $this->filterDestination = [];
        $this->filterDriver = [];
        $this->filterPlateNumber = [];
        $this->filterCompletedFrom = '';
        $this->filterCompletedTo = '';
        $this->filterSortDirection = null;
        
        $this->appliedDestination = [];
        $this->appliedDriver = [];
        $this->appliedPlateNumber = [];
        $this->appliedCompletedFrom = null;
        $this->appliedCompletedTo = null;
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
            case 'plateNumber':
                $this->filterPlateNumber = [];
                $this->appliedPlateNumber = [];
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
            case 'plateNumber':
                $this->filterPlateNumber = array_values(array_filter($this->filterPlateNumber, fn($id) => $id != $value));
                $this->appliedPlateNumber = array_values(array_filter($this->appliedPlateNumber, fn($id) => $id != $value));
                break;
        }
        
        $this->checkFiltersActive();
        $this->resetPage();
    }
    
    private function checkFiltersActive()
    {
        $this->filtersActive = !empty($this->appliedDestination) ||
                              !empty($this->appliedDriver) ||
                              !empty($this->appliedPlateNumber) ||
                              !empty($this->appliedCompletedFrom) ||
                              !empty($this->appliedCompletedTo) ||
                              ($this->sortDirection !== null && $this->sortDirection !== 'desc');
    }
    
    public function render()
    {
        $location = Session::get('location_id');

        $query = DisinfectionSlip::with([
            'truck' => function($q) {
                $q->withTrashed();
            },
            'location' => function($q) {
                $q->withTrashed();
            },
            'destination' => function($q) {
                $q->withTrashed();
            },
            'driver' => function($q) {
                $q->withTrashed();
            },
            'hatcheryGuard' => function($q) {
                $q->withTrashed();
            },
            'receivedGuard' => function($q) {
                $q->withTrashed();
            }
        ])
            // COMPLETED ONLY - Only show slips received/completed by the current user
            ->where('status', 2)
            ->where(function($query) use ($location) {
                // Outgoing: show if created by current user
                $query->where(function($q) use ($location) {
                    $q->where('location_id', $location)
                      ->where('hatchery_guard_id', Auth::id());
                })
                // Incoming: show if received/completed by current user
                ->orWhere(function($q) use ($location) {
                    $q->where('destination_id', $location)
                      ->where('received_guard_id', Auth::id());
                });
            })
            
            // SEARCH
            ->when($this->search, function($q) {
                $q->where(function($query) {
                    $query->where('slip_id', 'like', '%' . $this->search . '%')
                          ->orWhereHas('truck', function($t) {
                              $t->withTrashed()->where('plate_number', 'like', '%' . $this->search . '%');
                          });
                });
            })
            
            // FILTERS
            ->when(!empty($this->appliedDestination), function($q) {
                $q->whereIn('destination_id', $this->appliedDestination);
            })
            ->when(!empty($this->appliedDriver), function($q) {
                $q->whereIn('driver_id', $this->appliedDriver);
            })
            ->when(!empty($this->appliedPlateNumber), function($q) {
                $q->whereIn('truck_id', $this->appliedPlateNumber);
            })
            ->when($this->appliedCompletedFrom, function($q) {
                $q->whereDate('completed_at', '>=', $this->appliedCompletedFrom);
            })
            ->when($this->appliedCompletedTo, function($q) {
                $q->whereDate('completed_at', '<=', $this->appliedCompletedTo);
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

        return view('livewire.trucks.truck-list-completed', [
            'slips' => $slips
        ]);
    }
}