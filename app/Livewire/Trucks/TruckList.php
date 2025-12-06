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

class TruckList extends Component
{
    use WithPagination;

    public $type = 'incoming'; // incoming or outgoing
    protected $paginationTheme = 'tailwind';
    
    public $search = '';
    public $showFilters = false;
        
    public $filterDateFrom;
    public $filterDateTo;
    public $filterStatus = '';
    
    public $appliedDateFrom = null;
    public $appliedDateTo = null;
    public $appliedStatus = '';
    
    public $filtersActive = false;
    
    public $availableStatuses = [
        0 => 'Ongoing',
        1 => 'Disinfecting',
    ];

    // Create Modal
    public $showCreateModal = false;
    public $showCancelCreateConfirmation = false;
    public $truck_id;
    public $destination_id;
    public $driver_id;
    public $reason_for_disinfection;
    
    // Search properties for dropdowns
    public $searchTruck = '';
    public $searchDestination = '';
    public $searchDriver = '';

    protected $listeners = ['slip-created' => '$refresh'];

    public function mount($type = 'incoming')
    {
        $this->type = $type;
        $this->filterDateFrom = null;
        $this->filterDateTo = null;

        // Check if we should open create modal from route parameter
        if (request()->has('openCreate') && $this->type === 'outgoing') {
            $this->showCreateModal = true;
        }
    }

    // Computed properties for dynamic dropdown data
    public function getTrucksProperty()
    {
        return Truck::all();
    }

    public function getLocationsProperty()
    {
        // Exclude the current location from the list
        $currentLocationId = Session::get('location_id');
        return Location::where('id', '!=', $currentLocationId)->get();
    }

    public function getDriversProperty()
    {
        return Driver::all();
    }
    
    // Computed properties for filtered options with search
    public function getTruckOptionsProperty()
    {
        $trucks = Truck::orderBy('plate_number')->get();
        $options = $trucks->pluck('plate_number', 'id');
        
        if (!empty($this->searchTruck)) {
            $searchTerm = strtolower($this->searchTruck);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
        }
        
        return $options->toArray();
    }
    
    public function getLocationOptionsProperty()
    {
        $currentLocationId = Session::get('location_id');
        $locations = Location::where('id', '!=', $currentLocationId)->orderBy('location_name')->get();
        $options = $locations->pluck('location_name', 'id');
        
        if (!empty($this->searchDestination)) {
            $searchTerm = strtolower($this->searchDestination);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
        }
        
        return $options->toArray();
    }
    
    public function getDriverOptionsProperty()
    {
        $drivers = Driver::orderBy('first_name')->get();
        $options = $drivers->pluck('full_name', 'id');
        
        if (!empty($this->searchDriver)) {
            $searchTerm = strtolower($this->searchDriver);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
        }
        
        return $options->toArray();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function applyFilters()
    {
        $this->appliedDateFrom = $this->filterDateFrom;
        $this->appliedDateTo = $this->filterDateTo;
        $this->appliedStatus = $this->filterStatus;
        $this->filtersActive = true;
        $this->resetPage();
    }

    public function cancelFilters()
    {
        if ($this->filtersActive) {
            $this->filterDateFrom = $this->appliedDateFrom;
            $this->filterDateTo = $this->appliedDateTo;
            $this->filterStatus = $this->appliedStatus;
        } else {
            $this->filterDateFrom = null;
            $this->filterDateTo = null;
            $this->filterStatus = '';
        }
    }

    public function clearFilters()
    {
        $this->filterDateFrom = null;
        $this->filterDateTo = null;
        $this->filterStatus = '';
    }

    public function openCreateModal()
    {
        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        // Use dispatch to reset form after modal animation completes
        $this->dispatch('modal-closed');
    }

    public function cancelCreate()
    {
        // Reset all form fields and close modals
        $this->resetCreateForm();
        $this->showCancelCreateConfirmation = false;
        $this->showCreateModal = false;
    }

    public function resetCreateForm()
    {
        $this->truck_id = null;
        $this->destination_id = null;
        $this->driver_id = null;
        $this->reason_for_disinfection = null;
        $this->resetErrorBag();
    }

    public function createSlip()
    {
        // Get current location to validate against
        $currentLocationId = Session::get('location_id');
        
        $this->validate([
            'truck_id' => 'required|exists:trucks,id',
            'destination_id' => [
                'required',
                'exists:locations,id',
                function ($attribute, $value, $fail) use ($currentLocationId) {
                    if ($value == $currentLocationId) {
                        $fail('The destination cannot be the same as the current location.');
                    }
                },
            ],
            'driver_id' => 'required|exists:drivers,id',
            'reason_for_disinfection' => 'nullable|string|max:1000',
        ]);

        $slip = DisinfectionSlip::create([
            'truck_id' => $this->truck_id,
            'destination_id' => $this->destination_id,
            'driver_id' => $this->driver_id,
            'reason_for_disinfection' => $this->reason_for_disinfection,
            'location_id' => $currentLocationId,
            'hatchery_guard_id' => Auth::id(),
            'status' => 0, // Ongoing
            'slip_id' => $this->generateSlipId(),
        ]);

        $this->dispatch('toast', message: 'Disinfection slip created successfully!', type: 'success');        
        
        // Close modal first
        $this->showCreateModal = false;
        
        // Then reset form and page after a brief delay
        $this->dispatch('modal-closed');
        $this->resetPage();
    }

    private function generateSlipId()
    {
        $year = now()->format('y'); // Last 2 digits of year
        
        // Get the last slip ID for this year (including soft-deleted ones)
        $lastSlip = DisinfectionSlip::withTrashed()
            ->where('slip_id', 'like', $year . '-%')
            ->orderBy('slip_id', 'desc')
            ->first();
        
        if ($lastSlip) {
            // Extract the number part and increment
            $lastNumber = (int) substr($lastSlip->slip_id, 3);
            $newNumber = $lastNumber + 1;
        } else {
            // First slip of the year
            $newNumber = 1;
        }
        
        // Format: YY-NNNNN (e.g., 25-00001)
        return $year . '-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }
    
    public function render()
    {
        $location = Session::get('location_id');

        // Base query with type filter FIRST
        $query = DisinfectionSlip::query();

        // Apply type-specific filter first (most restrictive)
        if ($this->type === 'incoming') {
            $query->where('destination_id', $location)
                  ->whereIn('status', [0, 1]);
        } else {
            $query->where('location_id', $location)
                  ->whereIn('status', [0, 1]);
        }

        // Then apply other filters
        $slips = $query
            // SEARCH (only search within already filtered type)
            ->when($this->search, function($q) {
                $q->where(function($query) {
                    $query->where('slip_id', 'like', '%' . $this->search . '%')
                          ->orWhereHas('truck', function($t) {
                              $t->where('plate_number', 'like', '%' . $this->search . '%');
                          });
                });
            })
            

            // DATE RANGE FILTER
            ->when($this->filtersActive && $this->appliedDateFrom, function($q) {
                $q->whereDate('created_at', '>=', $this->appliedDateFrom);
            })
            ->when($this->filtersActive && $this->appliedDateTo, function($q) {
                $q->whereDate('created_at', '<=', $this->appliedDateTo);
            })

            // STATUS FILTER 
            ->when($this->filtersActive && $this->appliedStatus !== '', function($q) {
                $q->where('status', $this->appliedStatus);
            })

            ->with('truck') // Load relationship only for final filtered results
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        return view('livewire.trucks.truck-list', [
            'slips' => $slips,
            'availableStatuses' => $this->availableStatuses,
            'trucks' => $this->trucks,
            'locations' => $this->locations,
            'drivers' => $this->drivers,
            'truckOptions' => $this->truckOptions,
            'locationOptions' => $this->locationOptions,
            'driverOptions' => $this->driverOptions,
        ]);
    }
}