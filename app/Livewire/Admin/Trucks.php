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
    public $filterStatus = '';
    public $filterOrigin = [];
    public $filterDestination = [];
    public $filterDriver = [];
    public $filterPlateNumber = [];
    public $filterCreatedFrom = '';
    public $filterCreatedTo = '';
    
    // Applied filters (stored separately)
    public $appliedStatus = '';
    public $appliedOrigin = [];
    public $appliedDestination = [];
    public $appliedDriver = [];
    public $appliedPlateNumber = [];
    public $appliedCreatedFrom = null;
    public $appliedCreatedTo = null;
    
    public $filtersActive = false;
    
    public $availableStatuses = [
        0 => 'Ongoing',
        1 => 'Disinfected',
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
    }

    // Computed property for locations
    public function getLocationsProperty()
    {
        return Location::orderBy('location_name')->get();
    }

    // Computed property for drivers
    public function getDriversProperty()
    {
        return Driver::orderBy('first_name')->get();
    }

    // Computed property for trucks
    public function getTrucksProperty()
    {
        return Truck::orderBy('plate_number')->get();
    }

    // Computed property for guards (users)
    public function getGuardsProperty()
    {
        return User::orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(function ($user) {
                $name = trim("{$user->first_name} {$user->middle_name} {$user->last_name}");
                return [$user->id => $name];
            });
    }

    // Computed property for available origins (excludes selected destination)
    public function getAvailableOriginsProperty()
    {
        $locations = Location::orderBy('location_name')->get();
        
        if ($this->destination_id) {
            return $locations->where('id', '!=', $this->destination_id)
                ->pluck('location_name', 'id');
        }
        
        return $locations->pluck('location_name', 'id');
    }

    // Computed property for available destinations (excludes selected origin)
    public function getAvailableDestinationsProperty()
    {
        $locations = Location::orderBy('location_name')->get();
        
        if ($this->location_id) {
            return $locations->where('id', '!=', $this->location_id)
                ->pluck('location_name', 'id');
        }
        
        return $locations->pluck('location_name', 'id');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function applyFilters()
    {
        $this->appliedStatus = $this->filterStatus;
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
                $this->appliedStatus = '';
                $this->filterStatus = '';
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
        $this->filtersActive = 
            $this->appliedStatus !== '' ||
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
        $this->filterStatus = '';
        $this->filterOrigin = [];
        $this->filterDestination = [];
        $this->filterDriver = [];
        $this->filterPlateNumber = [];
        $this->filterCreatedFrom = null;
        $this->filterCreatedTo = null;
        
        $this->appliedStatus = '';
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

    // Watch for changes to location_id or destination_id to update available options
    public function updatedLocationId()
    {
        // If destination is the same as origin, clear it
        if ($this->destination_id == $this->location_id) {
            $this->destination_id = null;
        }
    }

    public function updatedDestinationId()
    {
        // If origin is the same as destination, clear it
        if ($this->location_id == $this->destination_id) {
            $this->location_id = null;
        }
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
            ->when($this->filtersActive && $this->appliedStatus !== '', function($query) {
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
            'availableOrigins' => $this->availableOrigins,
            'availableDestinations' => $this->availableDestinations,
            'availableStatuses' => $this->availableStatuses,
        ]);
    }
}