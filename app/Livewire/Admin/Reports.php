<?php

namespace App\Livewire\Admin;

use App\Models\Report;
use App\Models\DisinfectionSlip as DisinfectionSlipModel;
use App\Models\Truck;
use App\Models\Location;
use App\Models\Driver;
use App\Models\User;
use App\Models\Attachment;
use App\Services\Logger;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class Reports extends Component
{
    use WithPagination;

    public $search = '';
    public $showFilters = false;
    
    // Sorting properties
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    
    // Filter properties
    public $filterReportType = null; // null = All, 'slip' = Slip, 'misc' = Miscellaneous
    public $filterResolved = '0'; // Default to Unresolved, null = All, 0 = Unresolved, 1 = Resolved
    public $filterCreatedFrom = '';
    public $filterCreatedTo = '';
    
    // Applied filters
    public $appliedReportType = null;
    public $appliedResolved = '0'; // Default to Unresolved
    public $appliedCreatedFrom = '';
    public $appliedCreatedTo = '';
    
    public $filtersActive = true; // Default to true since we filter by unresolved
    
    public $availableStatuses = [
        '0' => 'Unresolved',
        '1' => 'Resolved',
    ];
    
    // View Details Modal
    public $showDetailsModal = false;
    public $selectedReport = null;
    
    // Slip Details Modal (reusing showDetailsModal for slip, will be set when slip is selected)
    public $selectedSlip = null;
    public $showAttachmentModal = false;
    public $attachmentFile = null;
    public $showRemoveAttachmentConfirmation = false;
    
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
    
    // Protection flags
    public $isResolving = false;
    public $isDeleting = false;
    public $showSlipDeleteConfirmation = false;
    
    public function mount()
    {
        // Apply default filter on mount
        $this->applyFilters();
    }
    
    protected $queryString = ['search'];
    
    public function updatedFilterResolved($value)
    {
        if ($value === null || $value === '' || $value === false) {
            $this->filterResolved = null;
        } elseif (is_numeric($value) || $value === '0' || $value === '1') {
            $this->filterResolved = (string)$value;
        } else {
            $this->filterResolved = null;
        }
    }
    
    public function applySort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }
    
    public function applyFilters()
    {
        $this->appliedReportType = $this->filterReportType;
        $this->appliedResolved = $this->filterResolved;
        $this->appliedCreatedFrom = $this->filterCreatedFrom;
        $this->appliedCreatedTo = $this->filterCreatedTo;
        
        $this->filtersActive = !is_null($this->appliedReportType) ||
                               !is_null($this->appliedResolved) || 
                               !empty($this->appliedCreatedFrom) || 
                               !empty($this->appliedCreatedTo);
        
        $this->showFilters = false;
        $this->resetPage();
    }
    
    public function removeFilter($filterName)
    {
        switch ($filterName) {
            case 'report_type':
                $this->appliedReportType = null;
                $this->filterReportType = null;
                break;
            case 'resolved':
                $this->appliedResolved = null;
                $this->filterResolved = null;
                break;
            case 'created_from':
                $this->appliedCreatedFrom = '';
                $this->filterCreatedFrom = '';
                break;
            case 'created_to':
                $this->appliedCreatedTo = '';
                $this->filterCreatedTo = '';
                break;
        }
        
        $this->filtersActive = !is_null($this->appliedReportType) ||
                               !is_null($this->appliedResolved) || 
                               !empty($this->appliedCreatedFrom) || 
                               !empty($this->appliedCreatedTo);
        
        $this->resetPage();
    }
    
    public function clearFilters()
    {
        $this->filterReportType = null;
        $this->filterResolved = null;
        $this->filterCreatedFrom = '';
        $this->filterCreatedTo = '';
        
        $this->appliedReportType = null;
        $this->appliedResolved = null;
        $this->appliedCreatedFrom = '';
        $this->appliedCreatedTo = '';
        
        $this->filtersActive = false;
        $this->resetPage();
    }
    
    public function openDetailsModal($reportId)
    {
        $this->selectedReport = Report::with(['user', 'slip', 'resolvedBy'])->findOrFail($reportId);
        $this->showDetailsModal = true;
    }
    
    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedReport = null;
        $this->selectedSlip = null;
        $this->showAttachmentModal = false;
        $this->attachmentFile = null;
    }
    
    public function openSlipDetailsModal($slipId)
    {
        // Close report details modal if open
        $this->showDetailsModal = false;
        $this->selectedReport = null;
        
        $this->selectedSlip = DisinfectionSlipModel::with([
            'truck' => function($q) { $q->withTrashed(); },
            'location',
            'destination',
            'driver',
            'attachment',
            'hatcheryGuard',
            'receivedGuard'
        ])->find($slipId);
        
        $this->showDetailsModal = true;
    }
    
    public function resolveReport()
    {
        // Prevent multiple submissions
        if ($this->isResolving) {
            return;
        }

        $this->isResolving = true;

        try {
            if (!$this->selectedReport) {
                $this->dispatch('toast', message: 'No report selected.', type: 'error');
                return;
            }

            $report = Report::findOrFail($this->selectedReport->id);
            $oldValues = [
                'resolved_at' => $report->resolved_at,
                'resolved_by' => $report->resolved_by,
                'description' => $report->description,
            ];
            
            // Atomic update: Only resolve if not already resolved to prevent race conditions
            $updated = Report::where('id', $this->selectedReport->id)
                ->whereNull('resolved_at') // Only update if not already resolved
                ->update([
                    'resolved_at' => now(),
                    'resolved_by' => Auth::id(),
                ]);
            
            if ($updated === 0) {
                // Report was already resolved by another process
                $report->refresh();
                $this->dispatch('toast', message: 'This report was already resolved by another administrator. Please refresh the page.', type: 'error');
                $this->closeDetailsModal();
                $this->resetPage();
                return;
            }
            
            // Refresh report to get updated data
            $report->refresh();
            
            $reportType = $report->slip_id ? "for slip " . ($report->slip->slip_id ?? 'N/A') : "for misc";
            $newValues = [
                'resolved_at' => $report->resolved_at,
                'resolved_by' => $report->resolved_by,
                'description' => $report->description,
            ];
            Logger::update(
                Report::class,
                $report->id,
                "Resolved report {$reportType}",
                $oldValues,
                $newValues
            );
            
            $this->dispatch('toast', message: 'Report marked as resolved.', type: 'success');
            $this->closeDetailsModal();
            $this->resetPage();
        } finally {
            $this->isResolving = false;
        }
    }

    public function unresolveReport()
    {
        // Prevent multiple submissions
        if ($this->isResolving) {
            return;
        }

        $this->isResolving = true;

        try {
            if (!$this->selectedReport) {
                $this->dispatch('toast', message: 'No report selected.', type: 'error');
                return;
            }

            $report = Report::findOrFail($this->selectedReport->id);
            $oldValues = [
                'resolved_at' => $report->resolved_at,
                'resolved_by' => $report->resolved_by,
            ];
            
            // Atomic update: Only unresolve if currently resolved to prevent race conditions
            $updated = Report::where('id', $this->selectedReport->id)
                ->whereNotNull('resolved_at') // Only update if currently resolved
                ->update([
                    'resolved_at' => null,
                    'resolved_by' => null,
                ]);
            
            if ($updated === 0) {
                // Report was already unresolved by another process
                $report->refresh();
                $this->dispatch('toast', message: 'This report was already unresolved by another administrator. Please refresh the page.', type: 'error');
                $this->closeDetailsModal();
                $this->resetPage();
                return;
            }
            
            // Refresh report to get updated data
            $report->refresh();
            
            $reportType = $report->slip_id ? "for slip " . ($report->slip->slip_id ?? 'N/A') : "for misc";
            $newValues = [
                'resolved_at' => null,
                'resolved_by' => null,
            ];
            Logger::update(
                Report::class,
                $report->id,
                "Unresolved report {$reportType}",
                $oldValues,
                $newValues
            );
            
            $this->dispatch('toast', message: 'Report marked as unresolved.', type: 'success');
            $this->closeDetailsModal();
            $this->resetPage();
        } finally {
            $this->isResolving = false;
        }
    }
    
    private function getFilteredReportsQuery()
    {
        $query = Report::with(['user', 'slip'])->whereNull('deleted_at');
        
        // Search
        if (!empty($this->search)) {
            $searchTerm = trim($this->search);
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('user', function ($userQuery) use ($searchTerm) {
                      $userQuery->where('first_name', 'like', '%' . $searchTerm . '%')
                                ->orWhere('last_name', 'like', '%' . $searchTerm . '%');
                  })
                  ->orWhereHas('slip', function ($slipQuery) use ($searchTerm) {
                      $slipQuery->where('slip_id', 'like', '%' . $searchTerm . '%');
                  })
                  ->orWhere(function ($miscQuery) use ($searchTerm) {
                      if (stripos($searchTerm, 'miscellaneous') !== false || stripos($searchTerm, 'misc') !== false) {
                          $miscQuery->whereNull('slip_id');
                      }
                  });
            });
        }
        
        // Filters
        if (!is_null($this->appliedReportType) && $this->appliedReportType !== '') {
            if ($this->appliedReportType === 'slip') {
                $query->whereNotNull('slip_id');
            } elseif ($this->appliedReportType === 'misc') {
                $query->whereNull('slip_id');
            }
        }
        
        if (!is_null($this->appliedResolved) && $this->appliedResolved !== '') {
            if ($this->appliedResolved == '1' || $this->appliedResolved === 1) {
                $query->whereNotNull('resolved_at');
            } else {
                $query->whereNull('resolved_at');
            }
        }
        
        if (!empty($this->appliedCreatedFrom)) {
            $query->whereDate('created_at', '>=', $this->appliedCreatedFrom);
        }
        
        if (!empty($this->appliedCreatedTo)) {
            $query->whereDate('created_at', '<=', $this->appliedCreatedTo);
        }
        
        // Sorting
        $query->orderBy($this->sortBy, $this->sortDirection);
        
        return $query;
    }
    
    public function render()
    {
        $reports = $this->getFilteredReportsQuery()->paginate(15);
        
        return view('livewire.admin.reports', [
            'reports' => $reports,
            'availableStatuses' => $this->availableStatuses,
            'trucks' => $this->getCachedTrucks(),
            'locations' => $this->getCachedLocations(),
            'drivers' => $this->getCachedDrivers(),
            'guards' => $this->getCachedGuards(),
            'editTruckOptions' => $this->editTruckOptions,
            'editDriverOptions' => $this->editDriverOptions,
            'editGuardOptions' => $this->editGuardOptions,
            'editReceivedGuardOptions' => $this->editReceivedGuardOptions,
            'editAvailableOriginsOptions' => $this->editAvailableOriginsOptions,
            'editAvailableDestinationsOptions' => $this->editAvailableDestinationsOptions,
        ]);
    }
    
    // Slip details modal methods (stubs for slip-details-modal component)
    public function canEdit()
    {
        if (!$this->selectedSlip) {
            return false;
        }

        // Admin cannot edit completed slips (status == 2 or completed_at is set)
        // Only SuperAdmins can edit completed slips
        return $this->selectedSlip->status != 2 && $this->selectedSlip->completed_at === null;
    }
    
    public function canDelete()
    {
        if (!$this->selectedSlip) {
            return false;
        }

        // Admin can delete any slip, including completed ones
        return true;
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
                $this->dispatch('toast', message: 'You are not authorized to delete this slip.', type: 'error');
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
                'reason_for_disinfection',
                'status'
            ]);
            
            // Atomic delete: Only delete if not already deleted to prevent race conditions
            $deleted = DisinfectionSlipModel::where('id', $this->selectedSlip->id)
                ->whereNull('deleted_at') // Only delete if not already deleted
                ->update(['deleted_at' => now()]);
            
            if ($deleted === 0) {
                // Slip was already deleted by another process
                $this->showSlipDeleteConfirmation = false;
                $this->dispatch('toast', message: 'This slip was already deleted by another administrator. Please refresh the page.', type: 'error');
                $this->selectedSlip->refresh();
                return;
            }
            
            // Log the delete action
            Logger::delete(
                DisinfectionSlipModel::class,
                $slipIdForLog,
                "Deleted slip {$slipId}",
                $oldValues
            );
            
            $this->showSlipDeleteConfirmation = false;
            $this->closeDetailsModal();
            $this->dispatch('toast', message: "Slip {$slipId} has been deleted.", type: 'success');
            $this->dispatch('slip-updated');
        } finally {
            $this->isDeleting = false;
        }
    }
    
    public function openEditModal()
    {
        if (!$this->selectedSlip) {
            return;
        }
        
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
            'editStatus' => 'required|in:0,1,2',
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

        // Status 0 (Ongoing): Origin and Hatchery Guard are required, Receiving Guard is optional
        if ($status == 0) {
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
        
        // Status 1 (Disinfecting) or 2 (Completed): Origin, Hatchery Guard, and Receiving Guard are all required
        if ($status == 1 || $status == 2) {
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

        // Status 0: Update origin and hatchery guard, receiving guard is optional
        if ($status == 0) {
            $updateData['location_id'] = $this->editLocationId;
            $updateData['hatchery_guard_id'] = $this->editHatcheryGuardId;
            $updateData['received_guard_id'] = $this->editReceivedGuardId; // Can be null
        }
        
        // Status 1 or 2: Update origin, hatchery guard, and receiving guard (required)
        if ($status == 1 || $status == 2) {
            $updateData['location_id'] = $this->editLocationId;
            $updateData['hatchery_guard_id'] = $this->editHatcheryGuardId;
            $updateData['received_guard_id'] = $this->editReceivedGuardId; // Required, validated above
        }

        $this->selectedSlip->update($updateData);

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
    
    public function openAttachmentModal($file)
    {
        $this->attachmentFile = $file;
        $this->showAttachmentModal = true;
    }
    
    public function closeAttachmentModal()
    {
        $this->showAttachmentModal = false;
        $this->attachmentFile = null;
    }
    
    public function canRemoveAttachment()
    {
        if (!$this->selectedSlip) {
            return false;
        }

        // Admin cannot remove attachment from completed slips (status == 2 or completed_at is set)
        // Only SuperAdmins can remove attachments from completed slips
        if ($this->selectedSlip->status == 2 || $this->selectedSlip->completed_at !== null) {
            return false;
        }

        return $this->selectedSlip->attachment_id !== null;
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
                // Delete the physical file from storage (except BGC.png logo)
                if ($attachment->file_path !== 'images/logo/BGC.png') {
                    if (Storage::disk('public')->exists($attachment->file_path)) {
                        Storage::disk('public')->delete($attachment->file_path);
                    }
                }

                // Remove attachment reference from slip
                $this->selectedSlip->update([
                    'attachment_id' => null,
                ]);

                // Hard delete the attachment record (except BGC.png logo)
                if ($attachment->file_path !== 'images/logo/BGC.png') {
                    $attachment->forceDelete();
                }

                // Refresh the slip
                $this->selectedSlip->refresh();
                $this->selectedSlip->load('attachment');

                // Close attachment modal and confirmation
                $this->showAttachmentModal = false;
                $this->showRemoveAttachmentConfirmation = false;
                $this->attachmentFile = null;

                $slipId = $this->selectedSlip->slip_id;
                $this->dispatch('toast', message: "{$slipId}'s attachment has been removed.", type: 'success');
            }

        } catch (\Exception $e) {
            Log::error('Attachment removal error: ' . $e->getMessage());
            $this->dispatch('toast', message: 'Failed to remove attachment. Please try again.', type: 'error');
        }
    }
}
