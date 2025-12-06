<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\DisinfectionSlip as DisinfectionSlipModel;
use App\Models\Attachment;
use App\Models\Truck;
use App\Models\Location;
use App\Models\Driver;
use App\Models\User;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Trucks extends Component
{
    use WithPagination;

    public $search = '';
    public $showFilters = false;
    
    // Filter fields
    public $filterStatus = null; // null = All Statuses, 0 = Ongoing, 1 = Disinfecting, 2 = Completed
    
    // Ensure filterStatus is properly typed when updated
    public function updatedFilterStatus($value)
    {
        // Handle null, empty string, or numeric values (0, 1, 2 matching backend)
        // null/empty = All Statuses, 0 = Ongoing, 1 = Disinfecting, 2 = Completed
        // The select will send values as strings, so we convert to int
        if ($value === null || $value === '' || $value === false) {
            $this->filterStatus = null;
        } elseif (is_numeric($value)) {
            $intValue = (int)$value;
            if ($intValue >= 0 && $intValue <= 2) {
                // Store as integer (0, 1, or 2)
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
    public $filterCreatedFrom = '';
    public $filterCreatedTo = '';
    
    // Search properties for filter dropdowns
    public $searchFilterPlateNumber = '';
    public $searchFilterDriver = '';
    public $searchFilterOrigin = '';
    public $searchFilterDestination = '';
    
    // Applied filters (stored separately)
    public $appliedStatus = null; // null = All Statuses, 0 = Ongoing, 1 = Disinfecting, 2 = Completed
    public $appliedOrigin = [];
    public $appliedDestination = [];
    public $appliedDriver = [];
    public $appliedPlateNumber = [];
    public $appliedCreatedFrom = null;
    public $appliedCreatedTo = null;
    
    public $filtersActive = false;
    
    public $availableStatuses = [
        0 => 'Ongoing',
        1 => 'Disinfecting',
        2 => 'Completed',
    ];

    // Details Modal
    public $showDetailsModal = false;
    public $showAttachmentModal = false;
    public $showCancelConfirmation = false;
    public $showDeleteConfirmation = false;
    public $showRemoveAttachmentConfirmation = false;
    public $selectedSlip = null;
    public $attachmentFile = null;
    public $isEditing = false;

    // Editable fields
    public $truck_id;
    public $destination_id;
    public $driver_id;
    public $reason_for_disinfection;

    // Original values for cancel
    private $originalValues = [];

    // Create Modal
    public $showCreateModal = false;
    public $showCancelCreateConfirmation = false;
    public $location_id; // Origin
    public $hatchery_guard_id;
    
    // Search properties for dropdowns (create modal)
    public $searchOrigin = '';
    public $searchDestination = '';
    public $searchTruck = '';
    public $searchDriver = '';
    public $searchHatcheryGuard = '';
    
    // Search properties for details modal
    public $searchDetailsTruck = '';
    public $searchDetailsDestination = '';
    public $searchDetailsDriver = '';
    
    // Note: availableOriginsOptions and availableDestinationsOptions are now computed properties
    
    // Cached collections to avoid duplicate queries
    private $cachedLocations = null;
    private $cachedDrivers = null;
    private $cachedTrucks = null;
    private $cachedGuards = null;

    public function mount()
    {
        // Initialize array filters
        $this->filterOrigin = [];
        $this->filterDestination = [];
        $this->filterDriver = [];
        $this->filterPlateNumber = [];
        $this->appliedOrigin = [];
        $this->appliedDestination = [];
        $this->appliedDriver = [];
        $this->appliedPlateNumber = [];
        
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
            $this->cachedTrucks = Truck::orderBy('plate_number')->get();
        }
        return $this->cachedTrucks;
    }
    
    private function getCachedGuards()
    {
        if ($this->cachedGuards === null) {
            $this->cachedGuards = User::orderBy('first_name')
                ->orderBy('last_name')
                ->get()
                ->mapWithKeys(function ($user) {
                    $name = trim("{$user->first_name} {$user->middle_name} {$user->last_name}");
                    return [$user->id => $name];
                });
        }
        return $this->cachedGuards;
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
        return $this->getCachedGuards();
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
        
        if (!empty($this->searchHatcheryGuard)) {
            $searchTerm = strtolower($this->searchHatcheryGuard);
            $guards = $guards->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Ensure selected value is always included
            $guards = $this->ensureSelectedInOptions($guards, $this->hatchery_guard_id, $allOptions);
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
    
    // Computed properties for available origins and destinations (reactive)
    public function getAvailableOriginsOptionsProperty()
    {
        $locations = $this->getCachedLocations();
        
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
        $locations = $this->getCachedLocations();
        
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

    public function applyFilters()
    {
        // Use filterStatus directly - it's already an integer (0, 1, 2) or null
        // null = All Statuses (no filter), 0 = Ongoing, 1 = Disinfecting, 2 = Completed
        $this->appliedStatus = $this->filterStatus; // Already an int or null
        $this->appliedOrigin = $this->filterOrigin;
        $this->appliedDestination = $this->filterDestination;
        $this->appliedDriver = $this->filterDriver;
        $this->appliedPlateNumber = $this->filterPlateNumber;
        $this->appliedCreatedFrom = $this->filterCreatedFrom;
        $this->appliedCreatedTo = $this->filterCreatedTo;
        
        $this->updateFiltersActive();
        
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
        }
        
        $this->updateFiltersActive();
        $this->resetPage();
    }

    public function updateFiltersActive()
    {
        // Check if any filters are actually applied
        // Important: 0 is a valid status (Ongoing), so check for null explicitly
        $this->filtersActive = 
            ($this->appliedStatus !== null) ||
            !empty($this->appliedOrigin) ||
            !empty($this->appliedDestination) ||
            !empty($this->appliedDriver) ||
            !empty($this->appliedPlateNumber) ||
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
        $this->filterCreatedFrom = null;
        $this->filterCreatedTo = null;
        
        // Clear search properties
        $this->searchFilterPlateNumber = '';
        $this->searchFilterDriver = '';
        $this->searchFilterOrigin = '';
        $this->searchFilterDestination = '';
        
        $this->appliedStatus = null;
        $this->appliedOrigin = [];
        $this->appliedDestination = [];
        $this->appliedDriver = [];
        $this->appliedPlateNumber = [];
        $this->appliedCreatedFrom = null;
        $this->appliedCreatedTo = null;
        
        $this->filtersActive = false;
        $this->resetPage();
    }

    // ==================== DETAILS MODAL METHODS ====================

    public function openDetailsModal($id)
    {
        $this->selectedSlip = DisinfectionSlipModel::with([
            'truck',
            'location',
            'destination',
            'driver',
            'attachment',
            'hatcheryGuard',
            'receivedGuard'
        ])->find($id);

        // Preload fields for editing
        $this->truck_id                = $this->selectedSlip->truck_id;
        $this->destination_id          = $this->selectedSlip->destination_id;
        $this->driver_id               = $this->selectedSlip->driver_id;
        $this->reason_for_disinfection = $this->selectedSlip->reason_for_disinfection;

        $this->isEditing = false;
        $this->showDetailsModal = true;
    }

    public function canEdit()
    {
        if (!$this->selectedSlip) {
            return false;
        }

        // Admin can edit if not completed
        return $this->selectedSlip->status != 2;
    }

    public function canDelete()
    {
        if (!$this->selectedSlip) {
            return false;
        }

        // Admin can delete if not completed
        return $this->selectedSlip->status != 2;
    }

    public function canRemoveAttachment()
    {
        if (!$this->selectedSlip) {
            return false;
        }

        // Admin can only REMOVE attachment (not add), and only if not completed
        return $this->selectedSlip->attachment_id !== null 
            && $this->selectedSlip->status != 2;
    }

    public function editDetailsModal()
    {
        if (!$this->canEdit()) {
            $this->dispatch('toast', message: 'Cannot edit a completed slip.', type: 'error');
            return;
        }

        $this->isEditing = true;
        
        // Store original values before editing
        $this->originalValues = [
            'truck_id'                => $this->truck_id,
            'destination_id'          => $this->destination_id,
            'driver_id'               => $this->driver_id,
            'reason_for_disinfection' => $this->reason_for_disinfection,
        ];
    }

    public function confirmCancelEdit()
    {
        $this->showCancelConfirmation = true;
    }

    public function cancelEdit()
    {
        // Restore original values
        $this->truck_id                = $this->originalValues['truck_id'] ?? $this->selectedSlip->truck_id;
        $this->destination_id          = $this->originalValues['destination_id'] ?? $this->selectedSlip->destination_id;
        $this->driver_id               = $this->originalValues['driver_id'] ?? $this->selectedSlip->driver_id;
        $this->reason_for_disinfection = $this->originalValues['reason_for_disinfection'] ?? $this->selectedSlip->reason_for_disinfection;
        
        // Reset search properties
        $this->searchDetailsTruck = '';
        $this->searchDetailsDestination = '';
        $this->searchDetailsDriver = '';
        
        // Reset states
        $this->isEditing = false;
        $this->showCancelConfirmation = false;
        $this->originalValues = [];
    }

    public function confirmDeleteSlip()
    {
        $this->showDeleteConfirmation = true;
    }

    public function save()
    {
        if (!$this->canEdit()) {
            $this->dispatch('toast', message: 'Cannot edit a completed slip.', type: 'error');
            return;
        }

        $this->validate([
            'truck_id'                => 'required|exists:trucks,id',
            'destination_id'          => 'required|exists:locations,id',
            'driver_id'               => 'required|exists:drivers,id',
            'reason_for_disinfection' => 'required|string|max:500',
        ]);

        $this->selectedSlip->update([
            'truck_id'                => $this->truck_id,
            'destination_id'          => $this->destination_id,
            'driver_id'               => $this->driver_id,
            'reason_for_disinfection' => $this->reason_for_disinfection,
        ]);

        // Refresh the slip with relationships
        $this->selectedSlip->refresh();
        $this->selectedSlip->load([
            'truck',
            'location',
            'destination',
            'driver',
            'attachment',
            'hatcheryGuard',
            'receivedGuard'
        ]);

        $this->isEditing = false;
        $this->originalValues = [];
        $this->dispatch('toast', message: 'Slip updated successfully!', type: 'success');
    }

    public function deleteSlip()
    {
        if (!$this->canDelete()) {
            $this->dispatch('toast', message: 'Cannot delete a completed slip.', type: 'error');
            return;
        }

        $slipId = $this->selectedSlip->slip_id;
        
        // Soft delete the slip
        $this->selectedSlip->delete();
        
        // Close all modals
        $this->showDeleteConfirmation = false;
        $this->showDetailsModal = false;
        
        // Clear selected slip
        $this->selectedSlip = null;
        
        // Show success message
        $this->dispatch('toast', message: "Slip #{$slipId} deleted successfully!", type: 'success');
        
        // Reset page to refresh the list
        $this->resetPage();
    }

    public function closeDetailsModal()
    {
        $this->isEditing = false;
        $this->showCancelConfirmation = false;
        $this->showDeleteConfirmation = false;
        $this->showRemoveAttachmentConfirmation = false;
        $this->originalValues = [];
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
        $this->reason_for_disinfection = null;
        $this->searchOrigin = '';
        $this->searchDestination = '';
        $this->searchTruck = '';
        $this->searchDriver = '';
        $this->searchHatcheryGuard = '';
        $this->resetErrorBag();
    }

    public function hasUnsavedChanges()
    {
        return !empty($this->truck_id) || 
               !empty($this->location_id) || 
               !empty($this->destination_id) || 
               !empty($this->driver_id) || 
               !empty($this->hatchery_guard_id) || 
               !empty($this->reason_for_disinfection);
    }

    public function createSlip()
    {
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
            'hatchery_guard_id' => 'required|exists:users,id',
            'reason_for_disinfection' => 'nullable|string|max:1000',
        ], [], [
            'location_id' => 'Origin',
            'destination_id' => 'Destination',
            'truck_id' => 'Truck',
            'driver_id' => 'Driver',
            'hatchery_guard_id' => 'Hatchery Guard',
            'reason_for_disinfection' => 'Reason for Disinfection',
        ]);

        $slip = DisinfectionSlipModel::create([
            'truck_id' => $this->truck_id,
            'location_id' => $this->location_id,
            'destination_id' => $this->destination_id,
            'driver_id' => $this->driver_id,
            'hatchery_guard_id' => $this->hatchery_guard_id,
            'reason_for_disinfection' => $this->reason_for_disinfection,
            'status' => 0, // Ongoing
        ]);

        $this->dispatch('toast', message: 'Disinfection slip created successfully!', type: 'success');
        
        // Close modal and reset form
        $this->showCreateModal = false;
        $this->resetCreateForm();
        $this->resetPage();
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

    public function openAttachmentModal($file)
    {
        $this->attachmentFile = $file;
        $this->showAttachmentModal = true;
    }

    public function closeAttachmentModal()
    {
        $this->showAttachmentModal = false;
        $this->js('setTimeout(() => $wire.clearAttachment(), 300)');
    }

    public function clearAttachment()
    {
        $this->attachmentFile = null;
    }

    public function confirmRemoveAttachment()
    {
        $this->showRemoveAttachmentConfirmation = true;
    }

    public function removeAttachment()
    {
        try {
            if (!$this->canRemoveAttachment()) {
                $this->dispatch('toast', message: 'Cannot remove attachment from a completed slip.', type: 'error');
                return;
            }

            // Check if attachment exists
            if (!$this->selectedSlip->attachment_id) {
                $this->dispatch('toast', message: 'No attachment found to remove.', type: 'error');
                return;
            }

            // Get the attachment record
            $attachment = Attachment::find($this->selectedSlip->attachment_id);

            if ($attachment) {
                // Delete the physical file from storage
                if (Storage::disk('public')->exists($attachment->file_path)) {
                    Storage::disk('public')->delete($attachment->file_path);
                }

                // Remove attachment reference from slip
                $this->selectedSlip->update([
                    'attachment_id' => null,
                ]);

                // Hard delete the attachment record
                $attachment->forceDelete();

                // Refresh the slip
                $this->selectedSlip->refresh();
                $this->selectedSlip->load('attachment');

                // Close attachment modal and confirmation
                $this->showAttachmentModal = false;
                $this->showRemoveAttachmentConfirmation = false;
                $this->attachmentFile = null;

                $this->dispatch('toast', message: 'Attachment removed successfully!', type: 'success');
            }

        } catch (\Exception $e) {
            Log::error('Attachment removal error: ' . $e->getMessage());
            $this->dispatch('toast', message: 'Failed to remove attachment. Please try again.', type: 'error');
        }
    }

    public function render()
    {
        $slips = DisinfectionSlipModel::with(['truck', 'location', 'destination', 'driver'])
            // Search
            ->when($this->search, function($query) {
                $query->where('slip_id', 'like', '%' . $this->search . '%')
                    ->orWhereHas('truck', function($q) {
                        $q->where('plate_number', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('driver', function($q) {
                        $q->where('first_name', 'like', '%' . $this->search . '%')
                          ->orWhere('last_name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('location', function($q) {
                        $q->where('location_name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('destination', function($q) {
                        $q->where('location_name', 'like', '%' . $this->search . '%');
                    });
            })
            // Status filter
            // Important: Check for null explicitly, as 0 is a valid status (Ongoing)
            ->when($this->filtersActive && $this->appliedStatus !== null, function($query) {
                // appliedStatus is already an integer (0, 1, or 2)
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
            // Created date range filter
            ->when($this->filtersActive && $this->appliedCreatedFrom, function($query) {
                $query->whereDate('created_at', '>=', $this->appliedCreatedFrom);
            })
            ->when($this->filtersActive && $this->appliedCreatedTo, function($query) {
                $query->whereDate('created_at', '<=', $this->appliedCreatedTo);
            })
            ->orderBy('created_at', 'desc')
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
            'filterOriginOptions' => $this->filterOriginOptions,
            'filterDestinationOptions' => $this->filterDestinationOptions,
            'createTruckOptions' => $this->createTruckOptions,
            'createDriverOptions' => $this->createDriverOptions,
            'createGuardOptions' => $this->createGuardOptions,
            'detailsTruckOptions' => $this->detailsTruckOptions,
            'detailsLocationOptions' => $this->detailsLocationOptions,
            'detailsDriverOptions' => $this->detailsDriverOptions,
        ]);
    }
}