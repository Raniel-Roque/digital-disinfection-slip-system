<?php

namespace App\Livewire\Trucks;

use Livewire\Component;
use App\Models\DisinfectionSlip as DisinfectionSlipModel;
use App\Models\Attachment;
use App\Models\Truck;
use App\Models\Location;
use App\Models\Driver;
use App\Models\Report;
use App\Services\Logger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class DisinfectionSlip extends Component
{
    public $showDetailsModal = false;
    public $showAttachmentModal = false;
    public $showAddAttachmentModal = false;
    public $showCancelConfirmation = false;
    public $showDeleteConfirmation = false;
    public $showDisinfectingConfirmation = false;
    public $showCompleteConfirmation = false;
    public $showIncompleteConfirmation = false;
    public $showRemoveAttachmentConfirmation = false;
    public $showReportModal = false;
    public $selectedSlip = null;
    public $currentAttachmentIndex = 0;
    public $attachmentToDelete = null;
    public $reportDescription = '';

    public $isEditing = false;
    
    // Protection flags
    public $isDeleting = false;
    public $isSubmitting = false;
    
    // Type property: 'incoming' or 'outgoing'
    public $type;

    // Editable fields
    public $truck_id;
    public $destination_id;
    public $driver_id;
    public $reason_for_disinfection;

    // Search properties for dropdowns
    public $searchTruck = '';
    public $searchDestination = '';
    public $searchDriver = '';

    // Original values for cancel
    private $originalValues = [];

    protected $listeners = ['open-disinfection-details' => 'openDetailsModal'];

    public function mount($type = 'incoming')
    {
        $this->type = $type;
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
        $locations = Location::where('id', '!=', $currentLocationId)
            ->whereNull('deleted_at')
            ->where('disabled', false)
            ->orderBy('location_name')
            ->get();
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

    public function openDetailsModal($id, $type = null)
    {
        // Set the type if provided
        if ($type) {
            $this->type = $type;
        }
        
        $this->selectedSlip = DisinfectionSlipModel::with([
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
        ])->find($id);
    

        // preload fields for editing
        $this->truck_id                = $this->selectedSlip->truck_id;
        $this->destination_id          = $this->selectedSlip->destination_id;
        $this->driver_id               = $this->selectedSlip->driver_id;
        $this->reason_for_disinfection = $this->selectedSlip->reason_for_disinfection;

        $this->isEditing = false;
        $this->showDetailsModal = true;
    }

    /**
     * Check if the current user is disabled
     */
    private function isUserDisabled()
    {
        $user = Auth::user();
        return $user && $user->disabled;
    }

    public function canEdit()
    {
        if (!$this->selectedSlip || $this->isUserDisabled()) {
            return false;
        }

        // SuperAdmin can edit any slip
        if (Auth::user()->user_type == 2) {
            return true;
        }

        // Can edit ONLY on OUTGOING, except when Completed (3) or Incomplete (4)
        return $this->type === 'outgoing'
            && Auth::id() === $this->selectedSlip->hatchery_guard_id
            && $this->selectedSlip->location_id === Session::get('location_id')
            && !in_array($this->selectedSlip->status, [3, 4]);
    }

    public function canStartDisinfecting()
    {
        if (!$this->selectedSlip || $this->isUserDisabled()) {
            return false;
        }

        $currentLocation = Session::get('location_id');

        // Can start ONLY on OUTGOING when status is Pending (0)
        if ($this->type === 'outgoing') {
            return $this->selectedSlip->status == 0
                && Auth::id() === $this->selectedSlip->hatchery_guard_id 
                && $this->selectedSlip->location_id === $currentLocation;
        }

        // NOT available on INCOMING anymore
        return false;
    }

    public function canComplete()
    {
        if (!$this->selectedSlip || $this->isUserDisabled()) {
            return false;
        }

        $currentLocation = Session::get('location_id');

        // Can complete on OUTGOING when status is Disinfecting (1)
        if ($this->type === 'outgoing') {
            return $this->selectedSlip->status == 1
                && Auth::id() === $this->selectedSlip->hatchery_guard_id 
                && $this->selectedSlip->location_id === $currentLocation;
        }

        // Can complete on INCOMING when status is In-Transit (2)
        // Guards can only complete unclaimed slips or slips they claimed
        // SuperAdmins can complete any slip at their location
        if (Auth::user()->user_type === 2) {
            return $this->type === 'incoming'
                && $this->selectedSlip->status == 2
                && $this->selectedSlip->destination_id === $currentLocation
                && $this->selectedSlip->location_id !== $currentLocation;
        }

        // Regular guards: only if claimed by current user or unclaimed
        return $this->type === 'incoming'
            && $this->selectedSlip->status == 2
            && $this->selectedSlip->destination_id === $currentLocation
            && $this->selectedSlip->location_id !== $currentLocation
            && (is_null($this->selectedSlip->received_guard_id) || $this->selectedSlip->received_guard_id === Auth::id());
    }

    public function canDelete()
    {
        if (!$this->selectedSlip || $this->isUserDisabled()) {
            return false;
        }

        // SuperAdmin can delete any slip
        if (Auth::user()->user_type == 2) {
            return true;
        }

        // Can delete ONLY on OUTGOING, except when Completed (3) or Incomplete (4)
        return $this->type === 'outgoing'
            && Auth::id() === $this->selectedSlip->hatchery_guard_id
            && $this->selectedSlip->location_id === Session::get('location_id')
            && !in_array($this->selectedSlip->status, [3, 4]);
    }

    public function getHasChangesProperty()
    {
        if (!$this->isEditing || !$this->selectedSlip) {
            return false;
        }

        // Compare with original values stored when entering edit mode
        return $this->truck_id != ($this->originalValues['truck_id'] ?? $this->selectedSlip->truck_id) ||
               $this->destination_id != ($this->originalValues['destination_id'] ?? $this->selectedSlip->destination_id) ||
               $this->driver_id != ($this->originalValues['driver_id'] ?? $this->selectedSlip->driver_id) ||
               ($this->reason_for_disinfection ?? '') != ($this->originalValues['reason_for_disinfection'] ?? $this->selectedSlip->reason_for_disinfection ?? '');
    }

    public function canManageAttachment()
    {
        if (!$this->selectedSlip || $this->isUserDisabled()) {
            return false;
        }

        $currentLocation = Session::get('location_id');

        // Can manage attachment on INCOMING when status is In-Transit (2)
        if ($this->type === 'incoming'
            && $this->selectedSlip->status == 2
            && $this->selectedSlip->destination_id === $currentLocation
            && $this->selectedSlip->location_id !== $currentLocation) {
            return true;
        }

        // Can manage attachment on OUTGOING, except when Completed (3)
        if ($this->type === 'outgoing'
            && Auth::id() === $this->selectedSlip->hatchery_guard_id 
            && $this->selectedSlip->location_id === $currentLocation
            && $this->selectedSlip->status != 3) {
            return true;
        }

        return false;
    }

    public function editDetailsModal()
    {
        // Authorization check - must be hatchery guard and location must match
        if (!$this->canEdit()) {
            $this->dispatch('toast', message: 'You are not authorized to edit this slip.', type: 'error');
            return;
        }

        $this->isEditing = true;
        
        // Store original values before editing (normalize reason to ensure consistent comparison)
        $originalReason = $this->reason_for_disinfection ?? '';
        $originalReason = trim($originalReason);
        $originalReason = $originalReason === '' ? null : $originalReason;
        
        $this->originalValues = [
            'truck_id'                => $this->truck_id,
            'destination_id'          => $this->destination_id,
            'driver_id'               => $this->driver_id,
            'reason_for_disinfection' => $originalReason,
        ];
    }

    public function cancelEdit()
    {
        // Restore original values
        $this->truck_id                = $this->originalValues['truck_id'] ?? $this->selectedSlip->truck_id;
        $this->destination_id          = $this->originalValues['destination_id'] ?? $this->selectedSlip->destination_id;
        $this->driver_id               = $this->originalValues['driver_id'] ?? $this->selectedSlip->driver_id;
        $this->reason_for_disinfection = $this->originalValues['reason_for_disinfection'] ?? $this->selectedSlip->reason_for_disinfection;
        
        // Reset search properties
        $this->searchTruck = '';
        $this->searchDestination = '';
        $this->searchDriver = '';
        
        // Reset states
        $this->isEditing = false;
        $this->showCancelConfirmation = false;
        $this->originalValues = [];
    }

    public function startDisinfecting()
    {
        // Authorization check using canStartDisinfecting
        if (!$this->canStartDisinfecting()) {
            $this->dispatch('toast', message: 'You are not authorized to start disinfecting this slip.', type: 'error');
            return;
        }

        // Only for OUTGOING: Status 0 (Pending) -> 1 (Disinfecting)
        $updated = DisinfectionSlipModel::where('id', $this->selectedSlip->id)
            ->where('status', 0) // Only update if still Pending
            ->update(['status' => 1]);

        if ($updated === 0) {
            $this->dispatch('toast', message: 'This slip status has changed. Please refresh the page.', type: 'error');
            $this->selectedSlip->refresh();
            $this->selectedSlip->load(['truck', 'location', 'destination', 'driver', 'hatcheryGuard', 'receivedGuard']);
            return;
        }

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
        
        // Log the start disinfecting action
        Logger::update(
            DisinfectionSlipModel::class,
            $this->selectedSlip->id,
            "Started disinfecting slip {$slipId}",
            ['status' => 0],
            ['status' => 1]
        );
        
        $this->showDisinfectingConfirmation = false;
        $this->dispatch('toast', message: "{$slipId} disinfection has been started.", type: 'success');
        $this->dispatch('slip-updated');
    }

    public function completeDisinfection()
    {
        // Authorization check using canComplete
        if (!$this->canComplete()) {
            $this->dispatch('toast', message: 'You are not authorized to complete this disinfection.', type: 'error');
            return;
        }

        // For OUTGOING: Status 1 (Disinfecting) -> 2 (In-Transit)
        if ($this->type === 'outgoing') {
        $this->selectedSlip->update([
                'status' => 2, // In-Transit
            ]);

            $slipId = $this->selectedSlip->slip_id;

            // Dispatch global event for truck arrival notification
            if ($this->selectedSlip->destination_id) {
                $this->dispatch('truckArrival', [
                    'locationId' => $this->selectedSlip->destination_id,
                    'slipId' => $slipId,
                    'truckCount' => 1 // Each slip represents one truck
                ]);
            }

            // Log the action
            Logger::update(
                DisinfectionSlipModel::class,
                $this->selectedSlip->id,
                "Completed disinfection for slip {$slipId}, now In-Transit",
                ['status' => 1],
                ['status' => 2]
            );

            $this->showCompleteConfirmation = false;
            $this->dispatch('toast', message: "{$slipId} is now In-Transit.", type: 'success');
        }
        // For INCOMING: Status 2 (In-Transit) -> 3 (Completed) and set completed_at timestamp and received_guard_id
        else {
            $this->selectedSlip->update([
                'status' => 3,
            'completed_at' => now(),
                'received_guard_id' => Auth::id(),
        ]);

            $slipId = $this->selectedSlip->slip_id;
            
            // Log the complete disinfection action
            Logger::update(
                DisinfectionSlipModel::class,
                $this->selectedSlip->id,
                "Completed slip {$slipId}",
                ['status' => 2, 'completed_at' => null, 'received_guard_id' => $this->selectedSlip->received_guard_id],
                ['status' => 3, 'completed_at' => now(), 'received_guard_id' => Auth::id()]
            );
            
            $this->showCompleteConfirmation = false;
            $this->dispatch('toast', message: "{$slipId} has been completed.", type: 'success');
        }

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

        $this->dispatch('slip-updated');
    }

    public function markAsIncomplete()
    {
        // Authorization check using canComplete (same logic for incoming slips)
        if (!$this->canComplete() || $this->type !== 'incoming') {
            $this->dispatch('toast', message: 'You are not authorized to mark this disinfection as incomplete.', type: 'error');
            return;
        }

        // For INCOMING only: Status 2 (In-Transit) -> 4 (Incomplete)
        // Keep received_guard_id so incomplete slips show to the user who received them
        $oldReceivedGuardId = $this->selectedSlip->received_guard_id;
        $this->selectedSlip->update([
            'status' => 4, // Incomplete
            'completed_at' => now(), // Set completion timestamp for incomplete status
        ]);

        $slipId = $this->selectedSlip->slip_id;

        // Log the mark as incomplete action
        Logger::update(
            DisinfectionSlipModel::class,
            $this->selectedSlip->id,
            "Marked slip {$slipId} as incomplete",
            ['status' => 2, 'received_guard_id' => $oldReceivedGuardId, 'completed_at' => $this->selectedSlip->completed_at],
            ['status' => 4, 'received_guard_id' => $oldReceivedGuardId, 'completed_at' => now()]
        );

        $this->showIncompleteConfirmation = false;
        $this->dispatch('toast', message: "{$slipId} has been marked as incomplete.", type: 'warning');

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

        $this->dispatch('slip-updated');
    }

    public function deleteSlip()
    {
        // Prevent multiple submissions
        if ($this->isDeleting) {
            return;
        }

        $this->isDeleting = true;

        try {
        // Authorization check using canDelete
        if (!$this->canDelete()) {
            $this->dispatch('toast', message: 'You are not authorized to delete this slip.', type: 'error');
            return;
        }

        $slipId = $this->selectedSlip->slip_id;
        $slipIdForLog = $this->selectedSlip->id;
        $oldValues = $this->selectedSlip->only(['truck_id', 'destination_id', 'driver_id', 'location_id', 'status', 'slip_id']);
        
        // Soft delete the slip (sets deleted_at timestamp)
        $this->selectedSlip->delete();
        
        // Log the delete action
        Logger::delete(
            DisinfectionSlipModel::class,
            $slipIdForLog,
            "Deleted disinfection slip {$slipId}",
            $oldValues
        );
        
        // Close all modals
        $this->showDeleteConfirmation = false;
        $this->showDetailsModal = false;
        
        // Clear selected slip
        $this->selectedSlip = null;
        
        // Show success message
        $this->dispatch('toast', message: "{$slipId} has been deleted.", type: 'success');
        
        // Refresh the parent component list if needed
        $this->dispatch('slip-deleted');
        } finally {
            $this->isDeleting = false;
        }
    }

    public function save()
    {
        // Check if there are any changes to save
        if (!$this->hasChanges) {
            $this->dispatch('toast', message: 'No changes to save.', type: 'info');
            return;
        }

        // Authorization check before saving
        if (!$this->canEdit()) {
            $this->dispatch('toast', message: 'You are not authorized to save changes to this slip.', type: 'error');
            return;
        }

        // Get current location to validate against
        $currentLocationId = Session::get('location_id');
        
        $this->validate([
            'truck_id'                => 'required|exists:trucks,id',
            'destination_id'          => [
                'required',
                'exists:locations,id',
                function ($attribute, $value, $fail) use ($currentLocationId) {
                    if ($value == $currentLocationId) {
                        $fail('The destination cannot be the same as the current location.');
                    }
                },
            ],
            'driver_id'               => 'required|exists:drivers,id',
            'reason_for_disinfection' => 'nullable|string|max:1000',
        ]);

        // Sanitize reason_for_disinfection
        $sanitizedReason = $this->sanitizeText($this->reason_for_disinfection);

        $this->selectedSlip->update([
            'truck_id'                => $this->truck_id,
            'destination_id'          => $this->destination_id,
            'driver_id'               => $this->driver_id,
            'reason_for_disinfection' => $sanitizedReason,
        ]);

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
        
        // Log the update action
        $oldValues = [
            'truck_id' => $this->originalValues['truck_id'] ?? null,
            'destination_id' => $this->originalValues['destination_id'] ?? null,
            'driver_id' => $this->originalValues['driver_id'] ?? null,
            'reason_for_disinfection' => $this->originalValues['reason_for_disinfection'] ?? null,
        ];
        $newValues = [
            'truck_id' => $this->truck_id,
            'destination_id' => $this->destination_id,
            'driver_id' => $this->driver_id,
            'reason_for_disinfection' => $sanitizedReason,
        ];
        
        Logger::update(
            DisinfectionSlipModel::class,
            $this->selectedSlip->id,
            "Updated disinfection slip {$slipId}",
            $oldValues,
            $newValues
        );
        
        $this->isEditing = false;
        $this->originalValues = [];
        $this->dispatch('toast', message: "{$slipId} has been updated.", type: 'success');
        $this->dispatch('slip-updated');
    }

    public function openReportModal()
    {
        if (!$this->selectedSlip) {
            $this->dispatch('toast', message: 'No slip selected.', type: 'error');
            return;
        }
        
        $this->reportDescription = '';
        $this->showReportModal = true;
    }
    
    public function submitReport()
    {
        if ($this->isSubmitting) {
            return;
        }

        if (!$this->selectedSlip) {
            $this->dispatch('toast', message: 'No slip selected.', type: 'error');
            return;
        }
        
        // Validate first before setting isSubmitting flag
        $this->validate([
            'reportDescription' => 'required|string|min:10|max:1000',
        ], [
            'reportDescription.required' => 'Please provide a reason for reporting.',
            'reportDescription.min' => 'The description must be at least 10 characters.',
            'reportDescription.max' => 'The description must not exceed 1000 characters.',
        ]);
        
        // Only set isSubmitting after validation passes
        $this->isSubmitting = true;
        
        try {
            $report = Report::create([
                'user_id' => Auth::id(),
                'slip_id' => $this->selectedSlip->id,
                'description' => $this->reportDescription,
            ]);
            
            // Log the report creation
            Logger::create(
                Report::class,
                $report->id,
                "Submitted report for slip {$this->selectedSlip->slip_id}",
                $report->only(['user_id', 'slip_id', 'description'])
            );
            
            $slipId = $this->selectedSlip->slip_id;
            $this->dispatch('toast', message: "Report submitted successfully for slip {$slipId}.", type: 'success');
            
            $this->closeReportModal();
        } catch (\Exception $e) {
            Log::error('Failed to create report: ' . $e->getMessage());
            $this->dispatch('toast', message: 'Failed to submit report. Please try again.', type: 'error');
            $this->isSubmitting = false;
        } finally {
            // Ensure isSubmitting is reset even if there's an error
            if (!$this->showReportModal) {
                $this->isSubmitting = false;
            }
        }
    }
    
    public function closeReportModal()
    {
        $this->showReportModal = false;
        $this->reportDescription = '';
        $this->isSubmitting = false;
        $this->resetValidation('reportDescription');
    }
    
    public function updatedShowReportModal($value)
    {
        // When modal is closed (via Alpine.js backdrop/close button), reset the form
        if (!$value) {
            $this->reportDescription = '';
            $this->isSubmitting = false;
            $this->resetValidation('reportDescription');
        }
    }

    public function closeDetailsModal()
    {
        // Reset all states when closing
        $this->isEditing = false;
        $this->showCancelConfirmation = false;
        $this->showDeleteConfirmation = false;
        $this->showDisinfectingConfirmation = false;
        $this->showCompleteConfirmation = false;
        $this->showIncompleteConfirmation = false;
        $this->showReportModal = false;
        $this->reportDescription = '';
        $this->originalValues = [];
        $this->showDetailsModal = false;
        $this->js('setTimeout(() => $wire.clearSelectedSlip(), 300)');
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
        $this->attachmentToDelete = null;
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

    public function openAddAttachmentModal()
    {
        // Authorization check using canManageAttachment
        if (!$this->canManageAttachment()) {
            $this->dispatch('toast', message: 'You are not authorized to add attachments.', type: 'error');
            return;
        }

        $this->showAddAttachmentModal = true;
        $this->dispatch('showAddAttachmentModal');
    }

    public function closeAddAttachmentModal()
    {
        $this->showAddAttachmentModal = false;
    }

    public function confirmRemoveCurrentAttachment()
    {
        $attachmentId = $this->getCurrentAttachmentId();
        if (!$attachmentId) {
            $this->dispatch('toast', message: 'No attachment selected.', type: 'error');
            return;
        }

        // Check permissions before showing confirmation
        if (!$this->canDeleteCurrentAttachment) {
            $this->dispatch('toast', message: 'You are not authorized to delete this attachment.', type: 'error');
            return;
        }

        $this->attachmentToDelete = $attachmentId;
        $this->showRemoveAttachmentConfirmation = true;
    }

    public function confirmRemoveAttachment($attachmentId)
    {
        if (!$attachmentId) {
            $this->dispatch('toast', message: 'No attachment selected.', type: 'error');
            return;
        }

        $this->attachmentToDelete = $attachmentId;
        $this->showRemoveAttachmentConfirmation = true;
    }

    public function getCurrentAttachmentId()
    {
        if (!$this->selectedSlip) {
            return null;
        }

        $attachments = $this->selectedSlip->attachments();
        if ($this->currentAttachmentIndex >= 0 && $this->currentAttachmentIndex < $attachments->count()) {
            return $attachments[$this->currentAttachmentIndex]->id;
        }
        return null;
    }

    public function getCanDeleteCurrentAttachmentProperty()
    {
        // First check if we can even get a current attachment ID
        $currentAttachmentId = $this->getCurrentAttachmentId();
        if (!$currentAttachmentId) {
            return false;
        }

        if (!$this->selectedSlip) {
            return false;
        }

        $attachments = $this->selectedSlip->attachments();
        if ($this->currentAttachmentIndex < 0 || $this->currentAttachmentIndex >= $attachments->count()) {
            return false;
        }

        $currentAttachment = $attachments[$this->currentAttachmentIndex];
        $user = Auth::user();
        $userType = $user->user_type ?? 0;
        $status = $this->selectedSlip->status;

        // COMPLETED SLIPS: Only Superadmin can delete photos
        if ($status === 3) {
            return $userType === 2; // Only Superadmin
        }

        // ACTIVE SLIPS: Check permissions based on user type
        if ($userType === 2) {
            // Superadmin can always delete
            return true;
        }

        if ($userType === 1) {
            // Admin can delete any photo from active slips
            return true;
        }

        // Check if user can manage attachments (for guards/users)
        $currentLocationId = Session::get('location_id');
        $isReceivingGuard = Auth::id() === $this->selectedSlip->received_guard_id;
        $isHatcheryGuard = Auth::id() === $this->selectedSlip->hatchery_guard_id;

        $canManage = ($status == 2 && $this->selectedSlip->destination_id === $currentLocationId && $this->selectedSlip->location_id !== $currentLocationId) ||
                    ($isHatcheryGuard && $this->selectedSlip->location_id === $currentLocationId && $status != 3);

        // Users/Guards can only delete their own photos if they have manage permission
        return $canManage && $currentAttachment->user_id === Auth::id();
    }

    public function removeAttachment()
    {
        try {
            // Check if user is admin or superadmin
            $user = Auth::user();
            $isAdminOrSuperAdmin = in_array($user->user_type, [1, 2]); // 1 = Admin, 2 = SuperAdmin
            
            // Authorization check using canManageAttachment (unless admin/superadmin)
            if (!$isAdminOrSuperAdmin && !$this->canManageAttachment()) {
                $this->dispatch('toast', message: 'You are not authorized to remove attachments.', type: 'error');
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
                // Check if current user is the one who uploaded this attachment (unless admin/superadmin)
                $user = Auth::user();
                $isAdminOrSuperAdmin = in_array($user->user_type, [1, 2]); // 1 = Admin, 2 = SuperAdmin
                
                if (!$isAdminOrSuperAdmin && $attachment->user_id !== Auth::id()) {
                    $this->dispatch('toast', message: 'You can only delete attachments that you uploaded.', type: 'error');
                    $this->showRemoveAttachmentConfirmation = false;
                    $this->attachmentToDelete = null;
                    return;
                }

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

                // Log the attachment deletion (except BGC.png logo)
                if ($attachment->file_path !== 'images/logo/BGC.png') {
                    // Capture old values for logging
                    $oldValues = [
                        'file_path' => $attachment->file_path,
                        'user_id' => $attachment->user_id,
                        'disinfection_slip_id' => $this->selectedSlip->id,
                        'slip_number' => $this->selectedSlip->slip_number,
                    ];

                    Logger::delete(
                        Attachment::class,
                        $attachment->id,
                        "Deleted attachment/photo from disinfection slip {$this->selectedSlip->slip_number}",
                        $oldValues,
                        ['related_slip' => $this->selectedSlip->id]
                    );

                    // Hard delete the attachment record
                    $attachment->forceDelete();
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
                } else {
                    // After deletion, reset to first attachment to avoid index confusion
                    $this->currentAttachmentIndex = 0;
                }

                // Close confirmation modal
                $this->showRemoveAttachmentConfirmation = false;
                $this->attachmentToDelete = null;

                // Dispatch event to refresh attachment modal data
                $this->dispatch('attachment-removed');

                $slipId = $this->selectedSlip->slip_id;
                $this->dispatch('toast', message: "Attachment has been removed from {$slipId}.", type: 'success');
            }

        } catch (\Exception $e) {
            Log::error('Attachment removal error: ' . $e->getMessage());
            $this->dispatch('toast', message: 'Failed to remove attachment. Please try again.', type: 'error');
        }
    }

    public function uploadAttachment($imageData)
    {
        try {
            // Authorization check using canManageAttachment
            if (!$this->canManageAttachment()) {
                $this->dispatch('toast', message: 'You are not authorized to add photos.', type: 'error');
                return;
            }

            // For INCOMING: Set received_guard_id if not already set (only for actual guards, not superadmins)
            if ($this->type === 'incoming' && !$this->selectedSlip->received_guard_id && Auth::user()->user_type === 0) {
                $this->selectedSlip->update([
                    'received_guard_id' => Auth::id(),
                ]);
                $this->selectedSlip->refresh();
            }

            // Get current attachment IDs (initialize as empty array if null)
            $attachmentIds = $this->selectedSlip->attachment_ids ?? [];

            // Validate image data format
            if (!preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/', $imageData)) {
                $this->dispatch('toast', message: 'Invalid image format. Only JPEG, PNG, GIF, and WebP are allowed.', type: 'error');
                return;
            }

            // Extract image type
            preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/', $imageData, $matches);
            $imageType = $matches[1];
            
            // Normalize jpg to jpeg
            if ($imageType === 'jpg') {
                $imageType = 'jpeg';
            }

            // Decode base64 image
            $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageDecoded = base64_decode($imageData);

            // Validate base64 decode success
            if ($imageDecoded === false) {
                $this->dispatch('toast', message: 'Failed to decode image data.', type: 'error');
                return;
            }

            // Validate file size (15MB max)
            $fileSizeInMB = strlen($imageDecoded) / 1024 / 1024;
            if ($fileSizeInMB > 15) {
                $this->dispatch('toast', message: 'Image size exceeds 15MB limit.', type: 'error');
                return;
            }

            // Validate image using getimagesizefromstring
            $imageInfo = @getimagesizefromstring($imageDecoded);
            if ($imageInfo === false) {
                $this->dispatch('toast', message: 'Invalid image file. Please capture a valid image.', type: 'error');
                return;
            }

            // Validate MIME type
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($imageInfo['mime'], $allowedMimeTypes)) {
                $this->dispatch('toast', message: 'Invalid image type. Only JPEG, PNG, GIF, and WebP are allowed.', type: 'error');
                return;
            }

            // Generate unique filename with correct extension
            $extension = $imageType;
            $filename = 'disinfection_slip_' . $this->selectedSlip->slip_id . '_' . time() . '_' . Str::random(8) . '.' . $extension;
            
            // Use Storage facade for consistency - save to public disk
            Storage::disk('public')->put('images/uploads/' . $filename, $imageDecoded);

            // Store relative path in database
            $relativePath = 'images/uploads/' . $filename;

            // Create attachment record
            $attachment = Attachment::create([
                'file_path' => $relativePath,
                'user_id' => Auth::id(),
            ]);

            // Add new attachment ID to array
            $attachmentIds[] = $attachment->id;

            // Update disinfection slip with attachment_ids array
            $this->selectedSlip->update([
                'attachment_ids' => $attachmentIds,
            ]);

            // Refresh the slip
            $this->selectedSlip->refresh();

            $slipId = $this->selectedSlip->slip_id;
            $totalAttachments = count($attachmentIds);
            $this->dispatch('toast', message: "Photo added to {$slipId} ({$totalAttachments} total).", type: 'success');
            
            // Don't close modal automatically - allow user to add more photos
            // $this->closeAddAttachmentModal();

        } catch (\Exception $e) {
            Log::error('Attachment upload error: ' . $e->getMessage());
            $this->dispatch('toast', message: 'Failed to upload attachment. Please try again.', type: 'error');
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
        return view('livewire.trucks.disinfection-slip');
    }
}