<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Issue;
use App\Models\DisinfectionSlip as DisinfectionSlipModel;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\Driver;
use App\Models\User;
use App\Models\Photo;
use App\Services\Logger;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Renderless;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Cache;
class Issues extends Component
{
    use WithPagination;

    protected $listeners = [
        'deleteSlip' => 'deleteSlip',
        'deleteIssue' => 'deleteIssue',
    ];

    public $search = '';
    public $showFilters = false;
    public $showDeleted = false;
    
    // Sorting properties
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    
    // Filter properties
    public $filterResolved = '0'; // Default to Unresolved, null = All, 0 = Unresolved, 1 = Resolved
    public $filterIssueType = null; // null = All, 'slip' = Slip, 'misc' = Miscellaneous
    public $filterCreatedFrom = '';
    public $filterCreatedTo = '';
    
    // Applied filters
    public $appliedResolved = '0'; // Default to Unresolved
    public $appliedIssueType = null;
    public $appliedCreatedFrom = '';
    public $appliedCreatedTo = '';
    
    public $filtersActive = true; // Default to true since we filter by unresolved
    public $excludeDeletedItems = true; // Default: exclude deleted items (automatically enabled)
    
    // Store previous date filter values when entering restore mode
    private $previousFilterCreatedFrom = null;
    private $previousFilterCreatedTo = null;
    private $previousAppliedCreatedFrom = null;
    private $previousAppliedCreatedTo = null;
    
    public $availableStatuses = [
        '0' => 'Unresolved',
        '1' => 'Resolved',
    ];
    
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
            $this->selectedSlip = DisinfectionSlipModel::withTrashed()->with([
                'vehicle' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
                'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
                'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
                'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
                'reason'
            ])->find($this->selectedSlip->id);
        }
        
        // Reload selectedIssue with trashed relations if it exists
        if ($this->selectedIssue && $this->selectedIssue->id) {
            $this->selectedIssue = $this->showDeleted
                ? Issue::onlyTrashed()->with([
                    'user' => function($q) { $q->withTrashed(); },
                    'slip' => function($q) {
                        $q->withTrashed();
                        $q->with([
                            'vehicle' => function($q) { $q->withTrashed(); },
                            'location' => function($q) { $q->withTrashed(); },
                            'destination' => function($q) { $q->withTrashed(); },
                            'driver' => function($q) { $q->withTrashed(); },
                            'hatcheryGuard' => function($q) { $q->withTrashed(); },
                            'receivedGuard' => function($q) { $q->withTrashed(); }
                        ]);
                    },
                    'resolvedBy' => function($q) { $q->withTrashed(); }
                ])->find($this->selectedIssue->id)
                : Issue::with([
                    'user' => function($q) { $q->withTrashed(); },
                    'slip' => function($q) {
                        $q->withTrashed();
                        $q->with([
                            'vehicle' => function($q) { $q->withTrashed(); },
                            'location' => function($q) { $q->withTrashed(); },
                            'destination' => function($q) { $q->withTrashed(); },
                            'driver' => function($q) { $q->withTrashed(); },
                            'hatcheryGuard' => function($q) { $q->withTrashed(); },
                            'receivedGuard' => function($q) { $q->withTrashed(); }
                        ]);
                    },
                    'resolvedBy' => function($q) { $q->withTrashed(); }
                ])->find($this->selectedIssue->id);
        }
    }
    
    /**
     * Prevent polling from running when any modal is open
     * This prevents the selected issue/slip data from being overwritten
     */
    #[On('polling')]
    public function polling()
    {
        // If any modal is open, skip polling
        if ($this->showFilters || $this->showDetailsModal || $this->showRestoreModal || 
            $this->showEditModal || $this->showAttachmentModal || $this->showDeleteConfirmation || 
            $this->showSlipDeleteConfirmation) {
            return;
        }

        // If a slip is selected, reload it with trashed relations
        if ($this->selectedSlip) {
            $this->selectedSlip = DisinfectionSlipModel::withTrashed()->with([
                'vehicle' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
                'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
                'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
                'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
                'reason'
            ])->find($this->selectedSlip->id);
        }
        // If a issue is selected, reload it with slip and slip's trashed relations
        if ($this->selectedIssue) {
            $this->selectedIssue = $this->showDeleted
                ? Issue::onlyTrashed()->with([
                    'user' => function($q) { $q->withTrashed(); },
                    'slip' => function($q) {
                        $q->withTrashed();
                        $q->with([
                            'vehicle' => function($q) { $q->withTrashed(); },
                            'location' => function($q) { $q->withTrashed(); },
                            'destination' => function($q) { $q->withTrashed(); },
                            'driver' => function($q) { $q->withTrashed(); },
                            'hatcheryGuard' => function($q) { $q->withTrashed(); },
                            'receivedGuard' => function($q) { $q->withTrashed(); }
                        ]);
                    },
                    'resolvedBy' => function($q) { $q->withTrashed(); }
                ])->find($this->selectedIssue->id)
                : Issue::with([
                    'user' => function($q) { $q->withTrashed(); },
                    'slip' => function($q) {
                        $q->withTrashed();
                        $q->with([
                            'vehicle' => function($q) { $q->withTrashed(); },
                            'location' => function($q) { $q->withTrashed(); },
                            'destination' => function($q) { $q->withTrashed(); },
                            'driver' => function($q) { $q->withTrashed(); },
                            'hatcheryGuard' => function($q) { $q->withTrashed(); },
                            'receivedGuard' => function($q) { $q->withTrashed(); }
                        ]);
                    },
                    'resolvedBy' => function($q) { $q->withTrashed(); }
                ])->find($this->selectedIssue->id);
        }
    }
    
    // Delete confirmation
    public $showDeleteConfirmation = false;
    public $selectedIssueId = null;
    public $showSlipDeleteConfirmation = false;
    public $showRemoveAttachmentConfirmation = false;
    public $attachmentToDelete = null;
    
    // Restore confirmation
    public $showRestoreModal = false;
    public $selectedIssueName = null;
    
    // Protection flags
    public $isDeleting = false;
    public $isRestoring = false;
    public $isResolving = false;
    
    // View Details Modal
    public $showDetailsModal = false;

    #[Locked]
    public $selectedIssue = null;
    #[Locked]
    public $selectedSlip = null;
     
    public $showAttachmentModal = false;
    public $attachmentFile = null;
    public $currentAttachmentIndex = 0;
    
    // Edit Modal
    public $showEditModal = false;
    public $showCancelEditConfirmation = false;
    public $editVehicleId;
    public $editLocationId; // Origin (for status 0)
    public $editDestinationId;
    public $editDriverId;
    public $editHatcheryGuardId; // For status 0
    public $editReceivedGuardId = null;
    public $editRemarksForDisinfection;
    public $editStatus;
    
    // Search properties for edit modal
    public $searchEditVehicle = '';
    public $searchEditOrigin = '';
    public $searchEditDestination = '';
    public $searchEditDriver = '';
    public $searchEditHatcheryGuard = '';
    public $searchEditReceivedGuard = '';
    
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
    
    public function toggleDeletedView()
    {
        $this->showDeleted = !$this->showDeleted;
        
        if ($this->showDeleted) {
            // Entering restore mode: Store current values only if not already stored, then clear date filters
            if ($this->previousAppliedCreatedFrom === null && $this->previousAppliedCreatedTo === null) {
                $this->previousFilterCreatedFrom = $this->filterCreatedFrom;
                $this->previousFilterCreatedTo = $this->filterCreatedTo;
                $this->previousAppliedCreatedFrom = $this->appliedCreatedFrom;
                $this->previousAppliedCreatedTo = $this->appliedCreatedTo;
            }
            
            $this->filterCreatedFrom = '';
            $this->filterCreatedTo = '';
            $this->appliedCreatedFrom = '';
            $this->appliedCreatedTo = '';
        } else {
            // Exiting restore mode: Always restore previous values, then reset stored values
            $this->filterCreatedFrom = $this->previousFilterCreatedFrom ?? '';
            $this->filterCreatedTo = $this->previousFilterCreatedTo ?? '';
            $this->appliedCreatedFrom = $this->previousAppliedCreatedFrom ?? '';
            $this->appliedCreatedTo = $this->previousAppliedCreatedTo ?? '';
            
            // Reset stored values for next time
            $this->previousFilterCreatedFrom = null;
            $this->previousFilterCreatedTo = null;
            $this->previousAppliedCreatedFrom = null;
            $this->previousAppliedCreatedTo = null;
        }
        
        $this->resetPage();
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
        $this->appliedResolved = $this->filterResolved;
        $this->appliedIssueType = $this->filterIssueType;
        $this->appliedCreatedFrom = $this->filterCreatedFrom;
        $this->appliedCreatedTo = $this->filterCreatedTo;
        
        $this->filtersActive = !is_null($this->appliedResolved) || 
                               !is_null($this->appliedIssueType) ||
                               !empty($this->appliedCreatedFrom) || 
                               !empty($this->appliedCreatedTo) ||
                               $this->excludeDeletedItems;
        
        $this->showFilters = false;
        $this->resetPage();
    }
    
    public function removeFilter($filterName)
    {
        switch ($filterName) {
            case 'resolved':
                $this->appliedResolved = null;
                $this->filterResolved = null;
                break;
            case 'issue_type':
                $this->appliedIssueType = null;
                $this->filterIssueType = null;
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
        
        $this->filtersActive = !is_null($this->appliedResolved) || 
                               !is_null($this->appliedIssueType) ||
                               !empty($this->appliedCreatedFrom) || 
                               !empty($this->appliedCreatedTo);
        
        $this->resetPage();
    }
    
    public function clearFilters()
    {
        $this->filterResolved = null;
        $this->filterIssueType = null;
        $this->filterCreatedFrom = '';
        $this->filterCreatedTo = '';
        
        $this->appliedResolved = null;
        $this->appliedIssueType = null;
        $this->appliedCreatedFrom = '';
        $this->appliedCreatedTo = '';
        
        $this->excludeDeletedItems = false;
        $this->filtersActive = false;
        $this->resetPage();
    }
    
    public function openDetailsModal($issueId)
    {
        // Set modal state FIRST to prevent polling from interfering
        $this->showDetailsModal = true;
        
        $this->selectedIssue = $this->showDeleted
            ? Issue::onlyTrashed()->with([
                'user' => function($q) { $q->withTrashed(); },
                'slip' => function($q) {
                    $q->withTrashed();
                    $q->with([
                        'vehicle' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
                        'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                        'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                        'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
                        'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
                        'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); }
                    ]);
                },
                'resolvedBy' => function($q) { $q->withTrashed(); }
            ])->findOrFail($issueId)
            : Issue::with([
                'user' => function($q) { $q->withTrashed(); },
                'slip' => function($q) {
                    $q->withTrashed();
                    $q->with([
                        'vehicle' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
                        'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                        'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                        'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
                        'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
                        'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); }
                    ]);
                },
                'resolvedBy' => function($q) { $q->withTrashed(); }
            ])->findOrFail($issueId);
    }
    
    public function getIssueTypeProperty()
    {
        if (!$this->selectedIssue) {
            return null;
        }
        return $this->selectedIssue->slip_id ? 'slip' : 'misc';
    }
    
    public function getSelectedSlipAttachmentsProperty()
    {
        if (!$this->selectedSlip || empty($this->selectedSlip->photo_ids)) {
            return collect([]);
        }
        
        return Photo::whereIn('id', $this->selectedSlip->photo_ids)
            ->with(['user' => function($q) { $q->withTrashed(); }])
            ->get();
    }
    
    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedIssue = null;
        $this->selectedSlip = null;
        $this->showAttachmentModal = false;
        $this->attachmentFile = null;
    }
    
    public function openSlipDetailsModal($slipId)
    {
        // Close issue details modal if open
        $this->selectedIssue = null;
        
        // Set modal state FIRST to prevent polling from interfering
        $this->showDetailsModal = true;

        $this->selectedSlip = DisinfectionSlipModel::withTrashed()->with([
            'vehicle' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
            'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
            'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
            'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
            'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
            'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
            'reason'
        ])->find($slipId);
    }

    public function confirmDeleteSlip()
    {
        $this->showDeleteConfirmation = true;
    }
    
    public function closeRestoreModal()
    {
        $this->showRestoreModal = false;
        $this->selectedIssueId = null;
        $this->selectedIssueName = null;
    }
    
    // Cached collections for edit modal
    private $cachedLocations = null;
    private $cachedDrivers = null;
    private $cachedVehicles = null;
    
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
    
    // NOTE: Old edit options properties removed - now using paginated dropdowns (same as Vehicles/Admin Issues)
    
    #[Renderless]
    public function getPaginatedVehicles($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = Vehicle::query()->whereNull('deleted_at')->where('disabled', false)->select(['id', 'vehicle']);
        if (!empty($search)) $query->where('vehicle', 'like', '%' . $search . '%');
        if (!empty($includeIds)) return ['data' => Vehicle::whereIn('id', $includeIds)->orderBy('vehicle')->get()->pluck('vehicle', 'id')->toArray(), 'has_more' => false, 'total' => count($includeIds)];
        $query->orderBy('vehicle');
        $offset = ($page - 1) * $perPage;
        $total = $query->count();
        return ['data' => $query->skip($offset)->take($perPage)->get()->pluck('vehicle', 'id')->toArray(), 'has_more' => ($offset + $perPage) < $total, 'total' => $total];
    }

    #[Renderless]
    public function getPaginatedDrivers($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = Driver::query()->whereNull('deleted_at')->where('disabled', false)->select(['id', 'first_name', 'middle_name', 'last_name']);
        if (!empty($search)) $query->where(function($q) use ($search) { $q->where('first_name', 'like', "%$search%")->orWhere('middle_name', 'like', "%$search%")->orWhere('last_name', 'like', "%$search%"); });
        if (!empty($includeIds)) return ['data' => Driver::whereIn('id', $includeIds)->orderBy('first_name')->orderBy('last_name')->get()->mapWithKeys(fn($d) => [$d->id => trim("{$d->first_name} {$d->middle_name} {$d->last_name}")])->toArray(), 'has_more' => false, 'total' => count($includeIds)];
        $query->orderBy('first_name')->orderBy('last_name');
        $offset = ($page - 1) * $perPage;
        $total = $query->count();
        return ['data' => $query->skip($offset)->take($perPage)->get()->mapWithKeys(fn($d) => [$d->id => trim("{$d->first_name} {$d->middle_name} {$d->last_name}")])->toArray(), 'has_more' => ($offset + $perPage) < $total, 'total' => $total];
    }

    #[Renderless]
    public function getPaginatedGuards($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = User::query()->where('user_type', 0)->where('disabled', false)->select(['id', 'first_name', 'middle_name', 'last_name', 'username']);
        if (!empty($search)) $query->where(function($q) use ($search) { $q->where('first_name', 'like', "%$search%")->orWhere('middle_name', 'like', "%$search%")->orWhere('last_name', 'like', "%$search%")->orWhere('username', 'like', "%$search%"); });
        if (!empty($includeIds)) return ['data' => User::whereIn('id', $includeIds)->orderBy('first_name')->orderBy('last_name')->get()->mapWithKeys(fn($u) => [$u->id => trim("{$u->first_name} {$u->middle_name} {$u->last_name}") . " @{$u->username}"])->toArray(), 'has_more' => false, 'total' => count($includeIds)];
        $query->orderBy('first_name')->orderBy('last_name');
        $offset = ($page - 1) * $perPage;
        $total = $query->count();
        return ['data' => $query->skip($offset)->take($perPage)->get()->mapWithKeys(fn($u) => [$u->id => trim("{$u->first_name} {$u->middle_name} {$u->last_name}") . " @{$u->username}"])->toArray(), 'has_more' => ($offset + $perPage) < $total, 'total' => $total];
    }

    #[Renderless]
    public function getPaginatedLocations($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = Location::query()->whereNull('deleted_at')->where('disabled', false)->select(['id', 'location_name']);
        if (!empty($search)) $query->where('location_name', 'like', '%' . $search . '%');
        if (!empty($includeIds)) return ['data' => Location::whereIn('id', $includeIds)->orderBy('location_name')->get()->pluck('location_name', 'id')->toArray(), 'has_more' => false, 'total' => count($includeIds)];
        $query->orderBy('location_name');
        $offset = ($page - 1) * $perPage;
        $total = $query->count();
        return ['data' => $query->skip($offset)->take($perPage)->get()->pluck('location_name', 'id')->toArray(), 'has_more' => ($offset + $perPage) < $total, 'total' => $total];
    }
    
    // Slip details modal methods
    /**
     * Get the display text for the reason on the selected slip
     */
    public function getDisplayReasonProperty()
    {
        if (!$this->selectedSlip || !$this->selectedSlip->reason_id) {
            return 'N/A';
        }

        $reason = $this->selectedSlip->reason;
        return ($reason && !$reason->is_disabled) ? $reason->reason_text : 'N/A';
    }

    public function canEdit()
    {
        if (!$this->selectedSlip) {
            return false;
        }

        // SuperAdmin slip details are view-only - no editing allowed
        return false;
    }
    
    public function canDelete()
    {
        if (!$this->selectedSlip) {
            return false;
        }

        // SuperAdmin slip details are view-only - no deleting allowed
        return false;
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
                'vehicle_id',
                'location_id',
                'destination_id',
                'driver_id',
                'hatchery_guard_id',
                'received_guard_id',
                'remarks_for_disinfection',
                'status'
            ]);
            
            // Clean up photos before soft deleting the slip
            $this->selectedSlip->deleteAttachments();
            
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
        
        // Dispatch event to the Issues Edit component
        $this->dispatch('openEditModal', $this->selectedSlip->id);
    }

    #[On('slip-updated')]
    public function handleSlipUpdated()
    {
        $this->resetPage();
        if ($this->selectedSlip && $this->selectedSlip->id) {
            $this->selectedSlip = DisinfectionSlipModel::withTrashed()->with([
                'vehicle' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
                'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
                'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
                'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
                'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
            ])->find($this->selectedSlip->id);
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
        $this->editVehicleId = null;
        $this->editLocationId = null;
        $this->editDestinationId = null;
        $this->editDriverId = null;
        $this->editHatcheryGuardId = null;
        $this->editReceivedGuardId = null;
        $this->editRemarksForDisinfection = null;
        $this->editStatus = null;
        $this->searchEditVehicle = '';
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
        
        return $this->editVehicleId != $this->selectedSlip->vehicle_id ||
               $this->editLocationId != $this->selectedSlip->location_id ||
               $this->editDestinationId != $this->selectedSlip->destination_id ||
               $this->editDriverId != $this->selectedSlip->driver_id ||
               $this->editHatcheryGuardId != $this->selectedSlip->hatchery_guard_id ||
               $this->editReceivedGuardId != $this->selectedSlip->received_guard_id ||
               $this->editRemarksForDisinfection != $this->selectedSlip->remarks_for_disinfection ||
               $this->editStatus != $this->selectedSlip->status;
    }
    
    public function getHasChangesProperty()
    {
        return $this->hasEditUnsavedChanges();
    }
    
    public function saveEdit()
    {
        // Authorization check
        if (!$this->canEdit()) {
            $this->dispatch('toast', message: 'You are not authorized to edit this slip.', type: 'error');
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
            'editVehicleId' => 'required|exists:vehicles,id',
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
            'editRemarksForDisinfection' => 'nullable|string|max:1000',
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
            'editVehicleId' => 'Vehicle',
            'editLocationId' => 'Origin',
            'editDestinationId' => 'Destination',
            'editDriverId' => 'Driver',
            'editHatcheryGuardId' => 'Hatchery Guard',
            'editReceivedGuardId' => 'Receiving Guard',
            'editRemarksForDisinfection' => 'Remarks for Disinfection',
            'editStatus' => 'Status',
        ]);

        // Check if there are any changes
        if (!$this->hasEditUnsavedChanges()) {
            $this->dispatch('toast', message: 'No changes detected.', type: 'info');
            return;
        }

        // Sanitize remarks_for_disinfection
        $sanitizedRemarks = $this->sanitizeText($this->editRemarksForDisinfection);

        // Capture old values for logging
        $oldValues = $this->selectedSlip->only([
            'vehicle_id', 'location_id', 'destination_id', 'driver_id',
            'hatchery_guard_id', 'received_guard_id', 'remarks_for_disinfection', 'status'
        ]);

        // Build update data based on status
        $updateData = [
            'vehicle_id' => $this->editVehicleId,
            'destination_id' => $this->editDestinationId,
            'driver_id' => $this->editDriverId,
            'remarks_for_disinfection' => $sanitizedRemarks,
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

        // Refresh the slip with relationships (including if slip is deleted)
        $this->selectedSlip->refresh();
        $this->selectedSlip = DisinfectionSlipModel::withTrashed()->with([
            'vehicle' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
            'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
            'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
            'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
            'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
            'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
            'reason' => function($q) { $q->select('id', 'reason_text', 'disabled', 'deleted_at'); },
        ])->find($this->selectedSlip->id);

        $slipId = $this->selectedSlip->slip_id;
        
        // Log the update
        $newValues = $this->selectedSlip->only([
            'vehicle_id', 'location_id', 'destination_id', 'driver_id',
            'hatchery_guard_id', 'received_guard_id', 'remarks_for_disinfection', 'status'
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
            $this->selectedSlip = DisinfectionSlipModel::withTrashed()->with([
                'vehicle' => function($q) { $q->withTrashed(); },
                'location' => function($q) { $q->withTrashed(); },
                'destination' => function($q) { $q->withTrashed(); },
                'driver' => function($q) { $q->withTrashed(); },
                'hatcheryGuard' => function($q) { $q->withTrashed(); },
                'receivedGuard' => function($q) { $q->withTrashed(); },
                'reason'
            ])->find($this->selectedSlip->id);
        }
    }

    public function nextAttachment()
    {
        $photos = $this->selectedSlipAttachments;
        if ($this->currentAttachmentIndex < $photos->count() - 1) {
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

        // SuperAdmin can remove Photo from any slip, including completed ones
        $attachmentIds = $this->selectedSlip->photo_ids ?? [];
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
                $this->dispatch('toast', message: 'Cannot remove Photo from this slip.', type: 'error');
                return;
            }

            if (!$this->attachmentToDelete) {
                $this->dispatch('toast', message: 'No Photo specified to remove.', type: 'error');
                return;
            }

            // Get current Photo IDs
            $attachmentIds = $this->selectedSlip->photo_ids ?? [];
            
            if (empty($attachmentIds) || !in_array($this->attachmentToDelete, $attachmentIds)) {
                $this->dispatch('toast', message: 'Photo not found.', type: 'error');
                return;
            }

            // Get the Photo record
            $Photo = Photo::find($this->attachmentToDelete);

            if ($Photo) {
                // Delete the physical file from storage (except BGC.png logo)
                if ($Photo->file_path !== 'images/logo/BGC.png') {
                    if (Storage::disk('public')->exists($Photo->file_path)) {
                        Storage::disk('public')->delete($Photo->file_path);
                    }

                    // Log the Photo deletion
                    $oldValues = [
                        'file_path' => $Photo->file_path,
                        'user_id' => $Photo->user_id,
                        'disinfection_slip_id' => $this->selectedSlip->id,
                        'slip_number' => $this->selectedSlip->slip_id,
                    ];

                    Logger::delete(
                        Photo::class,
                        $Photo->id,
                        "Deleted Photo/photo from disinfection slip {$this->selectedSlip->slip_id}",
                        $oldValues,
                        ['related_slip' => $this->selectedSlip->id]
                    );

                    // Hard delete the Photo record
                    $Photo->forceDelete();
                }

                // Remove Photo ID from array
                $attachmentIds = array_values(array_filter($attachmentIds, fn($id) => $id != $this->attachmentToDelete));

                // Update slip with remaining Photo IDs (or null if empty)
                $this->selectedSlip->update([
                    'photo_ids' => empty($attachmentIds) ? null : $attachmentIds,
                ]);
            }

            // Refresh the slip
            $this->selectedSlip->refresh();

            // Adjust current index if needed
            $photos = $this->selectedSlipAttachments;
            if ($this->currentAttachmentIndex >= $photos->count() && $photos->count() > 0) {
                $this->currentAttachmentIndex = $photos->count() - 1;
            } elseif ($photos->count() === 0) {
                // No more photos, close modal
                $this->showAttachmentModal = false;
                $this->currentAttachmentIndex = 0;
            }

            // Close confirmation modal
            $this->showRemoveAttachmentConfirmation = false;
            $this->attachmentToDelete = null;

            $slipId = $this->selectedSlip->slip_id;
            $this->dispatch('toast', message: "Photo has been removed from {$slipId}.", type: 'success');

        } catch (\Exception $e) {
            Log::error('Photo removal error: ' . $e->getMessage());
            $this->dispatch('toast', message: 'Failed to remove Photo. Please try again.', type: 'error');
        }
    }
    
    public function resolveIssue()
    {
        // Prevent multiple submissions
        if ($this->isResolving) {
            return;
        }

        $this->isResolving = true;

        try {
            if (!$this->selectedIssue) {
                $this->dispatch('toast', message: 'No issue selected.', type: 'error');
                return;
            }

        $issue = $this->showDeleted 
                ? Issue::onlyTrashed()->findOrFail($this->selectedIssue->id)
                : Issue::findOrFail($this->selectedIssue->id);
            $oldValues = [
                'resolved_at' => $issue->resolved_at,
                'resolved_by' => $issue->resolved_by,
                'description' => $issue->description,
            ];
            
        // Atomic update: Only resolve if not already resolved to prevent race conditions
        $updated = Issue::where('id', $this->selectedIssue->id)
            ->whereNull('resolved_at') // Only update if not already resolved
            ->update([
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ]);
        
        if ($updated === 0) {
            // Issue was already resolved by another process
            $issue->refresh();
            $this->dispatch('toast', message: 'This issue was already resolved by another administrator. Please refresh the page.', type: 'error');
            $this->closeDetailsModal();
            $this->resetPage();
            return;
        }

        Cache::forget('issues_all');
        // Refresh issue to get updated data
        $issue->refresh();
        
        $issueType = $issue->slip_id ? "for slip " . ($issue->slip->slip_id ?? 'N/A') : "for misc";
            $newValues = [
                'resolved_at' => $issue->resolved_at,
                'resolved_by' => $issue->resolved_by,
                'description' => $issue->description,
            ];
        Logger::update(
            Issue::class,
            $issue->id,
            "Resolved issue {$issueType}",
            $oldValues,
            $newValues
        );
        
        $this->dispatch('toast', message: 'Issue marked as resolved.', type: 'success');
            $this->closeDetailsModal();
        $this->resetPage();
        } finally {
            $this->isResolving = false;
        }
    }

    public function unresolveIssue()
    {
        // Prevent multiple submissions
        if ($this->isResolving) {
            return;
        }

        $this->isResolving = true;

        try {
            if (!$this->selectedIssue) {
                $this->dispatch('toast', message: 'No issue selected.', type: 'error');
                return;
            }

        $issue = $this->showDeleted 
                ? Issue::onlyTrashed()->findOrFail($this->selectedIssue->id)
                : Issue::findOrFail($this->selectedIssue->id);
        $oldValues = [
            'resolved_at' => $issue->resolved_at,
            'resolved_by' => $issue->resolved_by,
        ];
        
        // Atomic update: Only unresolve if currently resolved to prevent race conditions
        $updated = Issue::where('id', $this->selectedIssue->id)
            ->whereNotNull('resolved_at') // Only update if currently resolved
            ->update([
                'resolved_at' => null,
                'resolved_by' => null,
            ]);
        
        if ($updated === 0) {
            // Issue was already unresolved by another process
            $issue->refresh();
            $this->dispatch('toast', message: 'This issue was already unresolved by another administrator. Please refresh the page.', type: 'error');
            $this->closeDetailsModal();
            $this->resetPage();
            return;
        }
        
        Cache::forget('issues_all');
        // Refresh issue to get updated data
        $issue->refresh();
        
        $issueType = $issue->slip_id ? "for slip " . ($issue->slip->slip_id ?? 'N/A') : "for misc";
        $newValues = [
            'resolved_at' => null,
            'resolved_by' => null,
        ];
        Logger::update(
            Issue::class,
            $issue->id,
            "Unresolved issue {$issueType}",
            $oldValues,
            $newValues
        );
        
        $this->dispatch('toast', message: 'Issue marked as unresolved.', type: 'success');
            $this->closeDetailsModal();
        $this->resetPage();
        } finally {
            $this->isResolving = false;
        }
    }
    
    public function confirmDelete($issueId)
    {
        $this->selectedIssueId = $issueId;
        $this->showDeleteConfirmation = true;
    }
    
    public function deleteIssue()
    {
        // Prevent multiple submissions
        if ($this->isDeleting) {
            return;
        }

        $this->isDeleting = true;

        try {
            $issue = Issue::findOrFail($this->selectedIssueId);
        $issueType = $issue->slip_id ? "for slip " . ($issue->slip->slip_id ?? 'N/A') : "for misc";
        $oldValues = $issue->only(['user_id', 'slip_id', 'description', 'resolved_at']);
        
        // Atomic delete: Only delete if not already deleted to prevent race conditions
        $deleted = Issue::where('id', $this->selectedIssueId)
            ->whereNull('deleted_at') // Only delete if not already deleted
            ->update(['deleted_at' => now()]);
        
        if ($deleted === 0) {
            // Issue was already deleted by another process
            $this->showDeleteConfirmation = false;
            $this->reset(['selectedIssueId']);
            $this->dispatch('toast', message: 'This issue was already deleted by another administrator. Please refresh the page.', type: 'error');
            $this->resetPage();
            return;
        }
        
        Logger::delete(
            Issue::class,
            $issue->id,
            "Deleted issue {$issueType}",
            $oldValues
        );
        
        Cache::forget('issues_all');
        $this->showDeleteConfirmation = false;
        $this->selectedIssueId = null;
        $this->dispatch('toast', message: 'Issue has been deleted.', type: 'success');
        $this->resetPage();
        } finally {
            $this->isDeleting = false;
        }
    }

    public function openDeleteConfirmation($issueId)
    {
        $this->selectedIssueId = $issueId;
        $this->showDeleteConfirmation = true;
    }

    public function openRestoreModal($issueId)
    {
        $issue = Issue::onlyTrashed()->with(['slip'])->findOrFail($issueId);
        $this->selectedIssueId = $issueId;
        $this->selectedIssueName = $issue->slip_id ? "for slip " . ($issue->slip->slip_id ?? 'N/A') : "for misc";
        $this->showRestoreModal = true;
    }

    public function restoreIssue()
    {
        // Prevent multiple submissions
        if ($this->isRestoring) {
            return;
        }

        $this->isRestoring = true;

        try {
        if (!$this->selectedIssueId) {
            return;
        }

        // Atomic restore: Only restore if currently deleted to prevent race conditions
        // Do the atomic update first, then load the model only if successful
        $restored = Issue::onlyTrashed()
            ->where('id', $this->selectedIssueId)
            ->update(['deleted_at' => null]);
        
        if ($restored === 0) {
            // Issue was already restored or doesn't exist
            $this->showRestoreModal = false;
            $this->reset(['selectedIssueId', 'selectedIssueName']);
            $this->dispatch('toast', message: 'This issue was already restored or does not exist. Please refresh the page.', type: 'error');
            $this->resetPage();
            return;
        }
        
        // Now load the restored issue
        $issue = Issue::with(['slip'])->findOrFail($this->selectedIssueId);
        $issueType = $issue->slip_id ? "for slip " . ($issue->slip->slip_id ?? 'N/A') : "for misc";
        
        Logger::restore(
            Issue::class,
            $issue->id,
            "Restored issue {$issueType}"
        );
        
        Cache::forget('issues_all');
        $this->showRestoreModal = false;
        $this->reset(['selectedIssueId', 'selectedIssueName']);
        $this->resetPage();
        $this->dispatch('toast', message: 'Issue has been restored.', type: 'success');
        } finally {
            $this->isRestoring = false;
        }
    }
    
    private function getFilteredIssuesQuery()
    {
        // Optimize relationship loading by only selecting needed fields
        // This significantly reduces memory usage with large datasets (5,000+ records)
        $query = $this->showDeleted
            ? Issue::onlyTrashed()->with([
                'user' => function($q) {
                    $q->withTrashed()->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'deleted_at');
                },
                'slip' => function($q) {
                    $q->withTrashed()->select('id', 'slip_id', 'vehicle_id', 'location_id', 'destination_id', 'driver_id', 'status', 'completed_at', 'deleted_at');
                }
            ])
            : Issue::with([
                'user' => function($q) {
                    $q->withTrashed()->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'deleted_at');
                },
                'slip' => function($q) {
                    $q->withTrashed()->select('id', 'slip_id', 'vehicle_id', 'location_id', 'destination_id', 'driver_id', 'status', 'completed_at', 'deleted_at');
                }
            ])->whereNull('deleted_at');
        
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
                    // Regular search by name, slip, issue ID, or misc
                    $q->whereHas('user', function ($userQuery) use ($searchTerm) {
                          $userQuery->where('first_name', 'like', '%' . $searchTerm . '%')
                                    ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                                    ->orWhere('username', 'like', '%' . $searchTerm . '%');
                      })
                      ->orWhereHas('slip', function ($slipQuery) use ($searchTerm) {
                          $slipQuery->where('slip_id', 'like', '%' . $searchTerm . '%');
                      })
                      ->orWhere('id', 'like', '%' . $searchTerm . '%') // Search by issue ID
                      ->orWhere(function ($miscQuery) use ($searchTerm) {
                          if (stripos($searchTerm, 'miscellaneous') !== false || stripos($searchTerm, 'misc') !== false) {
                              $miscQuery->whereNull('slip_id');
                          }
                      });
                }
            });
        }
        
        // Filters
        if (!is_null($this->appliedResolved) && $this->appliedResolved !== '') {
            if ($this->appliedResolved == '1' || $this->appliedResolved === 1) {
                $query->whereNotNull('resolved_at');
            } else {
                $query->whereNull('resolved_at');
            }
        }
        
        if (!is_null($this->appliedIssueType)) {
            if ($this->appliedIssueType === 'slip') {
                $query->whereNotNull('slip_id');
            } elseif ($this->appliedIssueType === 'misc') {
                $query->whereNull('slip_id');
            }
        }
        
        if (!empty($this->appliedCreatedFrom)) {
            $query->whereDate('created_at', '>=', $this->appliedCreatedFrom);
        }
        
        if (!empty($this->appliedCreatedTo)) {
            $query->whereDate('created_at', '<=', $this->appliedCreatedTo);
        }
        
        // Exclude deleted items filter
        if ($this->excludeDeletedItems && !$this->showDeleted) {
            // Exclude issues where related user or slip has been deleted
            // Use whereIn with subqueries for better performance than whereHas with large datasets
            $query->whereIn('user_id', function($subquery) {
                    $subquery->select('id')->from('users')->whereNull('deleted_at');
                })
                ->where(function ($q) {
                    // Either the issue has no slip (miscellaneous), or the slip exists and is not deleted
                    // Note: slip_id in issues table references disinfection_slips.id (primary key)
                    $q->whereNull('slip_id')
                      ->orWhereIn('slip_id', function($subquery) {
                          $subquery->select('id')->from('disinfection_slips')->whereNull('deleted_at');
                      });
                });
        }
        
        // Sorting
        $query->orderBy($this->sortBy, $this->sortDirection);
        
        return $query;
    }
    
    public function render()
    {
        $issues = $this->getFilteredIssuesQuery()->paginate(15);

        return view('livewire.super-admin.issues', [
            'issues' => $issues,
            'availableStatuses' => $this->availableStatuses,
            // Edit modal uses paginated dropdowns - no need to pass data collections
        ]);
    }
}
