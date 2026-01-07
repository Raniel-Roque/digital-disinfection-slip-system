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
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
class Reports extends Component
{
    use WithPagination;

    protected $listeners = [
        'deleteSlip' => 'deleteSlip',
        'deleteReport' => 'deleteReport',
    ];

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
    public $currentAttachmentIndex = 0;
    public $showRemoveAttachmentConfirmation = false;
    public $attachmentToDelete = null;

    public function getSelectedSlipAttachmentsProperty()
    {
        if (!$this->selectedSlip || empty($this->selectedSlip->attachment_ids)) {
            return collect([]);
        }
        
        return Attachment::whereIn('id', $this->selectedSlip->attachment_ids)
            ->with(['user' => function($q) { $q->withTrashed(); }])
            ->get();
    }
    
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
    // For delete confirmation modal used by admin-slip-edit-modal
    public $showDeleteConfirmation = false;
    public $selectedReportId = null;
    
    public function mount()
    {
        // Apply default filter on mount
        $this->applyFilters();
    }
    
    /**
     * Livewire lifecycle hook: After hydration, reload selected models with trashed relations
     * This ensures deleted relations remain accessible across requests
     */
    public function hydrate()
    {
        // Reload selectedSlip with trashed relations if it exists
        if ($this->selectedSlip && $this->selectedSlip->id) {
            $this->selectedSlip = DisinfectionSlipModel::with([
                'truck' => function($q) { $q->withTrashed(); },
                'location' => function($q) { $q->withTrashed(); },
                'destination' => function($q) { $q->withTrashed(); },
                'driver' => function($q) { $q->withTrashed(); },
                'hatcheryGuard' => function($q) { $q->withTrashed(); },
                'receivedGuard' => function($q) { $q->withTrashed(); }
            ])->find($this->selectedSlip->id);
        }
        
        // Reload selectedReport with trashed relations if it exists
        if ($this->selectedReport && $this->selectedReport->id) {
            $this->selectedReport = Report::with([
                'user' => function($q) { $q->withTrashed(); },
                'slip' => function($q) {
                    $q->withTrashed();
                    $q->with([
                        'truck' => function($q) { $q->withTrashed(); },
                        'location' => function($q) { $q->withTrashed(); },
                        'destination' => function($q) { $q->withTrashed(); },
                        'driver' => function($q) { $q->withTrashed(); },
                        'hatcheryGuard' => function($q) { $q->withTrashed(); },
                        'receivedGuard' => function($q) { $q->withTrashed(); }
                    ]);
                },
                'resolvedBy' => function($q) { $q->withTrashed(); }
            ])->find($this->selectedReport->id);
        }
    }
    
    /**
     * Prevent polling from running when any modal is open
     * This prevents the selected slip data from being overwritten
     */
    #[On('polling')]
    public function polling()
    {
        // If any modal is open, skip polling
        if ($this->showFilters || $this->showDetailsModal || 
            $this->showAttachmentModal || $this->showEditModal || 
            $this->showCancelEditConfirmation || $this->showRemoveAttachmentConfirmation ||
            $this->showSlipDeleteConfirmation || $this->showDeleteConfirmation) {
            return;
        }
        
        // Allow normal component update - Livewire will re-render
    }
    
    // Helper methods to get cached collections
    private function getCachedLocations()
    {
        return Cache::remember('locations_all', 300, function() {
            return Location::orderBy('location_name')->get();
        });
    }

    private function getCachedDrivers()
    {
        return Cache::remember('drivers_all', 300, function() {
            return Driver::orderBy('first_name')->get();
        });
    }

    private function getCachedTrucks()
    {
        return Cache::remember('trucks_all', 300, function() {
            return Truck::orderBy('plate_number')->get();
        });
    }
    
    private function getCachedGuards()
    {
        return Cache::remember('guards_all', 300, function() {
            return User::where('user_type', 0)
                ->where('disabled', false)
                ->orderBy('first_name')
                ->orderBy('last_name')->get()
                ->mapWithKeys(function ($user) {
                    $name = trim("{$user->first_name} {$user->middle_name} {$user->last_name}");
                    return [$user->id => $name];
                });
        });
    }
    
    // Helper method to ensure selected values are always included in filtered options
    private function ensureSelectedInOptions($options, $selectedValue, $allOptions)
    {
        if (empty($selectedValue)) {
            return $options;
        }
        
        $allOptionsArray = is_array($allOptions) ? $allOptions : $allOptions->toArray();
        $optionsArray = is_array($options) ? $options : $options->toArray();
        
        if (isset($allOptionsArray[$selectedValue]) && !isset($optionsArray[$selectedValue])) {
            $optionsArray[$selectedValue] = $allOptionsArray[$selectedValue];
        }
        
        return is_array($options) ? $optionsArray : collect($optionsArray);
    }
    
    // Computed properties for edit modal filtered options
    public function getEditTruckOptionsProperty()
    {
        $trucks = $this->getCachedTrucks();
        $allOptions = $trucks->pluck('plate_number', 'id');
        $options = $allOptions;
        
        if (!empty($this->searchEditTruck)) {
            $searchTerm = strtolower($this->searchEditTruck);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            $options = $this->ensureSelectedInOptions($options, $this->editTruckId, $allOptions);
        }
        
        return $options->toArray();
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
            $options = $this->ensureSelectedInOptions($options, $this->editDriverId, $allOptions);
        }
        
        return $options->toArray();
    }
    
    public function getEditGuardOptionsProperty()
    {
        $guards = $this->getCachedGuards();
        $allOptions = $guards;
        
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
            if ($this->editReceivedGuardId && $this->editReceivedGuardId != $this->editHatcheryGuardId) {
                $guards = $this->ensureSelectedInOptions($guards, $this->editReceivedGuardId, $allOptions);
            }
        }
        
        return $guards->toArray();
    }
    
    public function getEditAvailableOriginsOptionsProperty()
    {
        $locations = $this->getCachedLocations();
        
        $originOptions = $locations;
        if ($this->editDestinationId) {
            $originOptions = $originOptions->where('id', '!=', $this->editDestinationId);
        }
        $originOptions = $originOptions->pluck('location_name', 'id');
        
        if (!empty($this->searchEditOrigin)) {
            $searchTerm = strtolower($this->searchEditOrigin);
            $originOptions = $originOptions->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            if ($this->editLocationId && $this->editLocationId != $this->editDestinationId) {
                $allOptions = $locations->pluck('location_name', 'id');
                $originOptions = $this->ensureSelectedInOptions($originOptions, $this->editLocationId, $allOptions);
            }
        }
        
        return $originOptions->toArray();
    }
    
    public function getEditAvailableDestinationsOptionsProperty()
    {
        $locations = $this->getCachedLocations()->whereNull('deleted_at')->where('disabled', false);
        
        $destinationOptions = $locations;
        if ($this->editLocationId) {
            $destinationOptions = $destinationOptions->where('id', '!=', $this->editLocationId);
        }
        $destinationOptions = $destinationOptions->pluck('location_name', 'id');
        
        if (!empty($this->searchEditDestination)) {
            $searchTerm = strtolower($this->searchEditDestination);
            $destinationOptions = $destinationOptions->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            if ($this->editDestinationId && $this->editDestinationId != $this->editLocationId) {
                $allOptions = $locations->pluck('location_name', 'id');
                $destinationOptions = $this->ensureSelectedInOptions($destinationOptions, $this->editDestinationId, $allOptions);
            }
        }
        
        return $destinationOptions->toArray();
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
        // Set modal state FIRST to prevent polling from interfering
        $this->showDetailsModal = true;
        
        $this->selectedReport = Report::with([
            'user' => function($q) {
                $q->withTrashed();
            },
            'slip' => function($q) {
                $q->withTrashed();
            },
                'resolvedBy' => function($q) {
                    $q->withTrashed();
                }
        ])->find($reportId);
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
        $this->selectedReport = null;
        
        // Set modal state FIRST to prevent polling from interfering
        $this->showDetailsModal = true;
        
        $this->selectedSlip = DisinfectionSlipModel::with([
            'truck' => function($q) { $q->withTrashed(); },
            'location' => function($q) { $q->withTrashed(); },
            'destination' => function($q) { $q->withTrashed(); },
            'driver' => function($q) { $q->withTrashed(); },
            'hatcheryGuard' => function($q) { $q->withTrashed(); },
            'receivedGuard' => function($q) { $q->withTrashed(); }
        ])->find($slipId);
    }

    public function confirmDeleteSlip()
    {
        $this->showDeleteConfirmation = true;
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

            Cache::forget('reports_all');
            
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
            
            Cache::forget('reports_all');

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

    public function openDeleteConfirmation($reportId)
    {
        $this->selectedReportId = $reportId;
        $this->showDeleteConfirmation = true;
    }

    public function deleteReport()
    {
        // Prevent multiple submissions
        if ($this->isDeleting) {
            return;
        }

        $this->isDeleting = true;

        try {
            $report = Report::findOrFail($this->selectedReportId);
            $reportType = $report->slip_id ? "for slip " . ($report->slip->slip_id ?? 'N/A') : "for misc";
            $oldValues = $report->only(['user_id', 'slip_id', 'description', 'resolved_at']);

            // Atomic delete: Only delete if not already deleted to prevent race conditions
            $deleted = Report::where('id', $this->selectedReportId)
                ->whereNull('deleted_at') // Only delete if not already deleted
                ->update(['deleted_at' => now()]);

            if ($deleted === 0) {
                // Report was already deleted by another process
                $report->refresh();
                $this->showDeleteConfirmation = false;
                $this->selectedReportId = null;
                $this->dispatch('toast', message: 'This report was already deleted by another administrator. Please refresh the page.', type: 'error');
                return;
            }

            // Log the delete action
            Logger::delete(
                Report::class,
                $report->id,
                "Deleted report {$reportType}",
                $oldValues
            );

            $this->showDeleteConfirmation = false;
            $this->selectedReportId = null;
            $this->dispatch('toast', message: "Report #{$report->id} has been deleted.", type: 'success');
            $this->resetPage();
        } finally {
            $this->isDeleting = false;
        }
    }

    private function getFilteredReportsQuery()
{
    $query = Report::with([
        'user' => function($q) { $q->withTrashed(); },
        'slip' => function($q) { $q->withTrashed(); },
        'resolvedBy' => function($q) { $q->withTrashed(); }
    ])->whereNull('deleted_at');
    
    // ADMIN: Exclude reports with deleted users or deleted slips
    $query->whereHas('user', function($q) {
        $q->whereNull('deleted_at');
    })
    ->where(function($q) {
        // Either the report has no slip (miscellaneous), or the slip exists and is not deleted
        $q->whereNull('slip_id')
          ->orWhereHas('slip', function($slipQ) {
              $slipQ->whereNull('deleted_at');
          });
    });
    
    // Search
    if (!empty($this->search)) {
        $searchTerm = trim($this->search);
        $isUsernameSearch = str_starts_with($searchTerm, '@');
        $actualSearchTerm = $isUsernameSearch ? substr($searchTerm, 1) : $searchTerm;

        $query->where(function ($q) use ($searchTerm, $isUsernameSearch, $actualSearchTerm) {
            if ($isUsernameSearch && !empty($actualSearchTerm)) {
                // If search starts with @, only search by username
                $q->whereHas('user', function ($userQuery) use ($actualSearchTerm) {
                      $userQuery->where('username', 'like', '%' . $actualSearchTerm . '%');
                  });
            } else {
                // Regular search by name, slip, report ID, or misc
                $q->whereHas('user', function ($userQuery) use ($searchTerm) {
                      $userQuery->where('first_name', 'like', '%' . $searchTerm . '%')
                                ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                                ->orWhere('username', 'like', '%' . $searchTerm . '%');
                  })
                  ->orWhereHas('slip', function ($slipQuery) use ($searchTerm) {
                      $slipQuery->where('slip_id', 'like', '%' . $searchTerm . '%');
                  })
                  ->orWhere('id', 'like', '%' . $searchTerm . '%') // Search by report ID
                  ->orWhere(function ($miscQuery) use ($searchTerm) {
                      if (stripos($searchTerm, 'miscellaneous') !== false || stripos($searchTerm, 'misc') !== false) {
                          $miscQuery->whereNull('slip_id');
                      }
                  });
            }
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

            Cache::forget('reports_all');

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

        // Status handling: Receiving Guard is optional for non-completed statuses; required for Completed (3)
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
            // Receiving guard may be null for non-completed statuses
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
        
        // If the slip is being set to Completed (3), require the receiving guard; otherwise it's optional
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
            'hatcheryGuard',
            'receivedGuard'
        ]);

        Cache::forget('reports_all');

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
    
    public function openAttachmentModal($index = 0)
    {
        $this->currentAttachmentIndex = (int) $index;
        $this->attachmentFile = $index;
        $this->showAttachmentModal = true;
    }
    
    public function closeAttachmentModal()
    {
        $this->showAttachmentModal = false;
        $this->attachmentFile = null;
        $this->currentAttachmentIndex = 0;

        // Livewire re-hydrates models without trashed relations; reload for details modal
        if ($this->selectedSlip) {
            $this->selectedSlip = DisinfectionSlipModel::with([
                'truck' => function($q) { $q->withTrashed(); },
                'location' => function($q) { $q->withTrashed(); },
                'destination' => function($q) { $q->withTrashed(); },
                'driver' => function($q) { $q->withTrashed(); },
                'hatcheryGuard' => function($q) { $q->withTrashed(); },
                'receivedGuard' => function($q) { $q->withTrashed(); }
            ])->find($this->selectedSlip->id);
        }
    }

    public function nextAttachment()
    {
        $attachments = $this->selectedSlipAttachments;
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

        $attachmentIds = $this->selectedSlip->attachment_ids ?? [];
        return !empty($attachmentIds);
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

                    // Log the attachment deletion
                    $oldValues = [
                        'file_path' => $attachment->file_path,
                        'user_id' => $attachment->user_id,
                        'disinfection_slip_id' => $this->selectedSlip->id,
                        'slip_number' => $this->selectedSlip->slip_id,
                    ];

                    Logger::delete(
                        Attachment::class,
                        $attachment->id,
                        "Deleted attachment/photo from disinfection slip {$this->selectedSlip->slip_id}",
                        $oldValues,
                        ['related_slip' => $this->selectedSlip->id]
                    );

                    // Hard delete the attachment record
                    $attachment->forceDelete();
                }

                // Remove attachment ID from array
                $attachmentIds = array_values(array_filter($attachmentIds, fn($id) => $id != $this->attachmentToDelete));

                // Update slip with remaining attachment IDs (or null if empty)
                $this->selectedSlip->update([
                    'attachment_ids' => empty($attachmentIds) ? null : $attachmentIds,
                ]);
            }

            // Refresh the slip
            $this->selectedSlip->refresh();

            // Adjust current index if needed
            $attachments = $this->selectedSlipAttachments;
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

            Cache::forget('reports_all');

            $slipId = $this->selectedSlip->slip_id;
            $this->dispatch('toast', message: "Attachment has been removed from {$slipId}.", type: 'success');

        } catch (\Exception $e) {
            Log::error('Attachment removal error: ' . $e->getMessage());
            $this->dispatch('toast', message: 'Failed to remove attachment. Please try again.', type: 'error');
        }
    }
}
