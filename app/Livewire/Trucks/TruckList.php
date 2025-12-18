<?php

namespace App\Livewire\Trucks;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\DisinfectionSlip;
use App\Models\Truck;
use App\Models\Location;
use App\Models\Driver;
use App\Services\Logger;
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
    public $sortDirection = null; // null, 'asc', 'desc' (applied)
    public $filterSortDirection = null; // null, 'asc', 'desc' (temporary, in filter modal)
    
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
    public $isCreating = false;

    // Search properties for dropdowns
    public $searchTruck = '';
    public $searchDestination = '';
    public $searchDriver = '';

    protected $listeners = ['slip-created' => '$refresh'];

    public function mount($type = 'incoming')
    {
        $this->type = $type;
        
        // Outgoing: default filter to today
        // Incoming: no default date filter
        if ($this->type === 'outgoing') {
            $today = now()->format('Y-m-d');
            $this->filterDateFrom = $today;
            $this->filterDateTo = $today;
            $this->appliedDateFrom = $today;
            $this->appliedDateTo = $today;
        } else {
            $this->filterDateFrom = null;
            $this->filterDateTo = null;
        }
        
        $this->filterSortDirection = $this->sortDirection; // Initialize filter sort with current sort
        $this->checkFiltersActive();

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
    
    // Helper method to ensure selected values are always included in filtered options
    private function ensureSelectedInOptions($options, $selectedValue, $allOptions)
    {
        if (empty($selectedValue)) {
            return $options;
        }
        
        $allOptionsArray = is_array($allOptions) ? $allOptions : $allOptions->toArray();
        $optionsArray = is_array($options) ? $options : $options->toArray();
        
        // Add selected value if it's not already in the filtered options
        if (isset($allOptionsArray[$selectedValue]) && !isset($optionsArray[$selectedValue])) {
            $optionsArray[$selectedValue] = $allOptionsArray[$selectedValue];
        }
        
        return $optionsArray;
    }
    
    // Computed properties for filtered options with search
    public function getTruckOptionsProperty()
    {
        $trucks = Truck::orderBy('plate_number')->get();
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
        
        return is_array($options) ? $options : $options->toArray();
    }
    
    public function getLocationOptionsProperty()
    {
        $currentLocationId = Session::get('location_id');
        $locations = Location::where('id', '!=', $currentLocationId)->orderBy('location_name')->get();
        $allOptions = $locations->pluck('location_name', 'id');
        $options = $allOptions;
        
        if (!empty($this->searchDestination)) {
            $searchTerm = strtolower($this->searchDestination);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected value is always included
            $options = $this->ensureSelectedInOptions($options, $this->destination_id, $allOptions);
        }
        
        return is_array($options) ? $options : $options->toArray();
    }
    
    public function getDriverOptionsProperty()
    {
        $drivers = Driver::orderBy('first_name')->get();
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
        
        return is_array($options) ? $options : $options->toArray();
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
        $this->sortDirection = $this->filterSortDirection;
        $this->checkFiltersActive();
        $this->resetPage();
    }
    
    private function checkFiltersActive()
    {
        $this->filtersActive = !empty($this->appliedDateFrom) ||
                              !empty($this->appliedDateTo) ||
                              $this->appliedStatus !== '' ||
                              ($this->sortDirection !== null && $this->sortDirection !== 'desc');
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
        $this->filterSortDirection = null;
        $this->appliedDateFrom = null;
        $this->appliedDateTo = null;
        $this->appliedStatus = '';
        $this->sortDirection = null;
        $this->filtersActive = false;
        $this->resetPage();
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
        $this->searchTruck = '';
        $this->searchDestination = '';
        $this->searchDriver = '';
        $this->resetErrorBag();
    }

    /**
     * Check if the current user is disabled
     */
    private function isUserDisabled()
    {
        $user = Auth::user();
        return $user && $user->disabled;
    }

    public function createSlip()
    {
        // Prevent multiple submissions
        if ($this->isCreating) {
            return;
        }

        $this->isCreating = true;

        try {
        // Check if user is disabled
        if ($this->isUserDisabled()) {
            $this->dispatch('toast', message: 'Your account has been disabled. Please contact an administrator.', type: 'error');
            return;
        }

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

        // Sanitize reason_for_disinfection
        $sanitizedReason = $this->sanitizeText($this->reason_for_disinfection);

        $slip = DisinfectionSlip::create([
            'truck_id' => $this->truck_id,
            'destination_id' => $this->destination_id,
            'driver_id' => $this->driver_id,
            'reason_for_disinfection' => $sanitizedReason,
            'location_id' => $currentLocationId,
            'hatchery_guard_id' => Auth::id(),
            'status' => 0, // Ongoing
            'slip_id' => $this->generateSlipId(),
        ]);

        // Log the create action
        Logger::create(
            DisinfectionSlip::class,
            $slip->id,
            "Created disinfection slip {$slip->slip_id}",
            $slip->only(['truck_id', 'destination_id', 'driver_id', 'location_id', 'status'])
        );

        $this->dispatch('toast', message: 'Disinfection slip created successfully!', type: 'success');        
        
        // Close modal first
        $this->showCreateModal = false;
        
        // Then reset form and page after a brief delay
        $this->dispatch('modal-closed');
        $this->resetPage();
        } finally {
            $this->isCreating = false;
        }
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
        $location = Session::get('location_id');

        // Base query with type filter FIRST
        $query = DisinfectionSlip::query();

        // Apply type-specific filter first (most restrictive)
        if ($this->type === 'incoming') {
            // Incoming: Status 0 (Ongoing) - no auth required, Status 1 (Disinfecting) - only for auth guard
            $query->where('destination_id', $location)
                  ->where(function($q) {
                      $q->where('status', 0) // Ongoing - anyone can see
                        ->orWhere(function($q2) {
                            $q2->where('status', 1) // Disinfecting - only for auth guard
                               ->where('received_guard_id', Auth::id());
                        });
                  });
        } else {
            // Outgoing: only show slips created by the current user
            $query->where('location_id', $location)
                  ->where('hatchery_guard_id', Auth::id())
                  ->whereIn('status', [0, 1]);
        }

        // Then apply other filters
        $slips = $query
            // SEARCH (only search within already filtered type)
            ->when($this->search, function($q) {
                $q->where(function($query) {
                    $query->where('slip_id', 'like', '%' . $this->search . '%')
                          ->orWhereHas('truck', function($t) {
                              $t->withTrashed()->where('plate_number', 'like', '%' . $this->search . '%');
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

            ->with(['truck' => function($q) {
                $q->withTrashed();
            }]) // Load relationship with soft deleted records
            ->when($this->sortDirection === 'asc', function($q) {
                $q->orderBy('slip_id', 'asc');
            })
            ->when($this->sortDirection === 'desc', function($q) {
                $q->orderBy('slip_id', 'desc');
            })
            ->when($this->sortDirection === null, function($q) {
                $q->orderBy('created_at', 'desc'); // default
            })
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