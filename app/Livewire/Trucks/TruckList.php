<?php

namespace App\Livewire\Trucks;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\DisinfectionSlip;
use App\Models\Truck;
use App\Models\Location;
use App\Models\Driver;
use App\Models\Attachment;
use App\Models\Reason;
use App\Services\Logger;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
/**
 * @method void resetPage()
 * @method void dispatch(string $event, mixed ...$params)
 * @method void resetErrorBag()
 * @method array validate(array $rules)
 * @property-read \Illuminate\Database\Eloquent\Collection $trucks
 * @property-read \Illuminate\Database\Eloquent\Collection $locations
 * @property-read \Illuminate\Database\Eloquent\Collection $drivers
 * @property-read array $truckOptions
 * @property-read array $locationOptions
 * @property-read array $driverOptions
 */
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
        0 => 'Pending',
        1 => 'Disinfecting',
        2 => 'In-Transit',
    ];

    // Create Modal
    public $showCreateModal = false;
    public $showCancelCreateConfirmation = false;
    public $truck_id;
    public $destination_id;
    public $driver_id;
    public $reason_id;
    public $remarks_for_disinfection;
    public $isCreating = false;

    // Attachment properties for creation
    public $showAddAttachmentModal = false;
    public $pendingAttachmentIds = []; // Store attachment IDs before slip is created
    public $showRemovePendingAttachmentConfirmation = false;
    public $pendingAttachmentToDelete = null;
    
    // Pending Attachment Modal (for viewing pending attachments)
    public $showPendingAttachmentModal = false;
    public $currentPendingAttachmentIndex = 0;

    // Search properties for dropdowns
    public $searchTruck = '';
    public $searchDestination = '';
    public $searchDriver = '';
    public $searchReason = '';

    // Reason management properties (for super guards)
    public $showReasonsModal = false;
    public $showCreateReasonModal = false;
    public $newReasonText = '';
    public $editingReasonId = null;
    public $editingReasonText = '';
    public $originalReasonText = '';
    public $showSaveConfirmation = false;
    public $showUnsavedChangesConfirmation = false;
    public $savingReason = false;
    public $reasonTexts = [];
    public $showDeleteReasonConfirmation = false;
    public $reasonToDelete = null;
    public $searchReasonSettings = '';
    public $reasonsPage = 1; // Page for reasons pagination

    protected $listeners = ['slip-created' => '$refresh'];
    
    public function updatedSearchReasonSettings()
    {
        $this->reasonsPage = 1; // Reset to first page when search changes
    }

    public function mount($type = 'incoming')
    {
        $this->type = $type;
        
        // Clean up any orphaned pending attachments from previous session
        // This catches cases where user uploaded but didn't create slip and navigated away
        $this->cleanupOrphanedPendingAttachments();
        
        // Outgoing: set default filter values to today (for UI), but don't apply them automatically
        // Incoming: no default date filter
        if ($this->type === 'outgoing') {
            $today = now()->format('Y-m-d');
            $this->filterDateFrom = $today;
            $this->filterDateTo = $today;
            $this->appliedDateFrom = null;
            $this->appliedDateTo = null;
        } else {
            $this->filterDateFrom = null;
            $this->filterDateTo = null;
        }
        
        $this->filterSortDirection = $this->sortDirection; // Initialize filter sort with current sort
        $this->checkFiltersActive();

        // Check if we should open create modal from route parameter (only if location allows)
        if (request()->has('openCreate') && $this->type === 'outgoing' && $this->canCreateSlip) {
            $this->showCreateModal = true;
        }
        
        // Load reasons if user is a super guard
        if ($this->isSuperGuard()) {
            $this->loadReasons();
        }
    }
    
    public function isSuperGuard()
    {
        $user = Auth::user();
        return $user && $user->super_guard && $user->user_type === 0;
    }

    // Computed properties for dynamic dropdown data
    public function getTrucksProperty()
    {
        return $this->getCachedTrucks();
    }
    
    public function getLocationsProperty()
    {
        return $this->getCachedLocations();
    }
    
    public function getDriversProperty()
    {
        return $this->getCachedDrivers();
    }

    public function getCanCreateSlipProperty()
    {
        if ($this->type !== 'outgoing') {
            return false;
        }
        
        $currentLocationId = Session::get('location_id');
        if (!$currentLocationId) {
            return false;
        }
        
        $location = Location::find($currentLocationId, ['id', 'create_slip']);
        return $location && ($location->create_slip ?? false);
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
    
    // Helper methods to get cached collections
    // Only cache id and name fields to reduce memory usage with large datasets
    private function getCachedTrucks()
    {
        return Cache::remember('trucks_all', 300, function() {
            return Truck::withTrashed()
                ->whereNull('deleted_at')
                ->where('disabled', '=', false, 'and')
                ->select('id', 'plate_number', 'disabled', 'deleted_at')
                ->orderBy('plate_number', 'asc')
                ->get();
        });
    }
    
    private function getCachedLocations()
    {
        $currentLocationId = Session::get('location_id');
        return Cache::remember("locations_all_{$currentLocationId}", 300, function() use ($currentLocationId) {
            return Location::where('id', '!=', $currentLocationId, 'and')
                ->whereNull('deleted_at')
                ->where('disabled', '=', false, 'and')
                ->select('id', 'location_name', 'disabled', 'deleted_at')
                ->orderBy('location_name', 'asc')
                ->get();
        });
    }
    
    private function getCachedDrivers()
    {
        return Cache::remember('drivers_all', 300, function() {
            return Driver::withTrashed()
                ->whereNull('deleted_at')
                ->where('disabled', '=', false, 'and')
                ->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')
                ->orderBy('first_name', 'asc')
                ->get();
        });
    }
    
    // Computed properties for filtered options with search
    public function getTruckOptionsProperty()
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
        
        return is_array($options) ? $options : $options->toArray();
    }
    
    public function getLocationOptionsProperty()
    {
        $locations = $this->getCachedLocations();
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
        
        return is_array($options) ? $options : $options->toArray();
    }
    
    public function getReasonOptionsProperty()
    {
        // Get only non-disabled reasons for dropdown (disabled reasons cannot be selected)
        // Only cache id and reason_text to reduce memory usage
        $reasons = Cache::remember('reasons_active', 300, function() {
            return Reason::where('is_disabled', '=', false, 'and')
                ->select('id', 'reason_text', 'is_disabled')
                ->orderBy('reason_text', 'asc')
                ->get();
        });
        $allOptions = $reasons->pluck('reason_text', 'id');
        $options = $allOptions;
        
        if (!empty($this->searchReason)) {
            $searchTerm = strtolower($this->searchReason);
            $options = $options->filter(function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            });
            // Only include selected value if it's in the available (non-disabled) options
            if ($this->reason_id && isset($allOptions[$this->reason_id])) {
                $options = $this->ensureSelectedInOptions($options, $this->reason_id, $allOptions);
            }
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
        // Only check actual filters, not sorts (sorts are separate from filters)
        $this->filtersActive = !empty($this->appliedDateFrom) ||
                              !empty($this->appliedDateTo) ||
                              $this->appliedStatus !== '';
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
        // Authorization check: Only allow if location permits slip creation
        if (!$this->canCreateSlip) {
            $this->dispatch('toast', message: 'This location is not authorized to create slips.', type: 'error');
            return;
        }
        
        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        // Clean up pending attachments if modal is closed without creating
        $this->cleanupPendingAttachments();
        
        $this->showCreateModal = false;
        // Use dispatch to reset form after modal animation completes
        $this->dispatch('modal-closed');
    }

    public function updatedShowCreateModal($value)
    {
        // When modal is closed (set to false) and there are pending attachments, clean them up
        // This catches when modal is closed via backdrop click or X button
        if ($value === false && !empty($this->pendingAttachmentIds)) {
            $this->cleanupPendingAttachments();
        }
    }

    public function dehydrate()
    {
        // Clean up pending attachments when component is being dehydrated (page navigation, refresh, etc.)
        // This ensures cleanup even if modal wasn't properly closed or user navigates away
        if (!empty($this->pendingAttachmentIds)) {
            // Only cleanup if modal is not open (to avoid cleanup during normal operation)
            if (!$this->showCreateModal) {
                $this->cleanupPendingAttachments();
            }
        }
    }

    public function cancelCreate()
    {
        // Clean up pending attachments
        $this->cleanupPendingAttachments();
        
        // Reset all form fields and close modals
        $this->resetCreateForm();
        $this->showCancelCreateConfirmation = false;
        $this->showCreateModal = false;
    }

    private function cleanupPendingAttachments()
    {
        if (empty($this->pendingAttachmentIds)) {
            return;
        }
        
        // Store IDs to clean up before clearing the array
        $attachmentIdsToCleanup = $this->pendingAttachmentIds;
        
        // Clear the array immediately to prevent double cleanup
        $this->pendingAttachmentIds = [];
        
        // Optimize: Fetch all attachments in one query instead of N queries
        $attachments = Attachment::whereIn('id', $attachmentIdsToCleanup, 'and', false)->get();
        
        foreach ($attachments as $attachment) {
            try {
                // Delete the physical file from storage
                if (Storage::disk('public')->exists($attachment->file_path)) {
                    Storage::disk('public')->delete($attachment->file_path);
                }
                // Hard delete the attachment record
                $attachment->forceDelete();
            } catch (\Exception $e) {
                Log::error('Failed to cleanup attachment ' . $attachment->id . ': ' . $e->getMessage());
            }
        }
    }

    public function resetCreateForm()
    {
        $this->truck_id = null;
        $this->destination_id = null;
        $this->driver_id = null;
        $this->reason_id = null;
        $this->remarks_for_disinfection = null;
        $this->searchTruck = '';
        $this->searchDestination = '';
        $this->searchDriver = '';
        $this->searchReason = '';
        // Clean up pending attachments before clearing the array
        $this->cleanupPendingAttachments();
        $this->showAddAttachmentModal = false;
        $this->showRemovePendingAttachmentConfirmation = false;
        $this->pendingAttachmentToDelete = null;
        $this->showPendingAttachmentModal = false;
        $this->currentPendingAttachmentIndex = 0;
        $this->resetErrorBag();
    }

    /**
     * Clean up orphaned pending attachments that are not referenced by any slip
     * This catches cases where attachments were uploaded but slip creation was cancelled
     * and the cleanup didn't run (e.g., page refresh, navigation away)
     */
    private function cleanupOrphanedPendingAttachments()
    {
        // Find all attachments with "pending" in filename that are not referenced by any slip
        $orphanedAttachments = Attachment::where('file_path', 'like', 'images/uploads/disinfection_slip_pending_%')
            ->get()
            ->filter(function ($attachment) {
                // Check if this attachment is referenced by any slip
                $isReferenced = DisinfectionSlip::whereJsonContains('attachment_ids', $attachment->id)->exists();
                return !$isReferenced;
            });

        foreach ($orphanedAttachments as $attachment) {
            try {
                // Delete the physical file from storage
                if (Storage::disk('public')->exists($attachment->file_path)) {
                    Storage::disk('public')->delete($attachment->file_path);
                }
                // Hard delete the attachment record
                $attachment->forceDelete();
            } catch (\Exception $e) {
                Log::error('Failed to cleanup orphaned pending attachment ' . $attachment->id . ': ' . $e->getMessage());
            }
        }
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
        // Authorization check: Only allow if location permits slip creation
        if (!$this->canCreateSlip) {
            $this->dispatch('toast', message: 'This location is not authorized to create slips.', type: 'error');
            return;
        }
        
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
            'reason_id' => [
                'required',
                'exists:reasons,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $reason = Reason::find($value, ['id', 'is_disabled']);
                        if (!$reason || $reason->is_disabled) {
                            $fail('The selected reason is not available.');
                        }
                    }
                },
            ],
            'remarks_for_disinfection' => 'nullable|string|max:1000',
        ]);

        // Sanitize remarks_for_disinfection
        $sanitizedRemarks = $this->sanitizeText($this->remarks_for_disinfection);

        $slip = DisinfectionSlip::create([
            'truck_id' => $this->truck_id,
            'destination_id' => $this->destination_id,
            'driver_id' => $this->driver_id,
            'reason_id' => $this->reason_id,
            'remarks_for_disinfection' => $sanitizedRemarks,
            'location_id' => $currentLocationId,
            'hatchery_guard_id' => Auth::id(),
            'status' => 0, // Pending
            'slip_id' => $this->generateSlipId(),
            'attachment_ids' => !empty($this->pendingAttachmentIds) ? $this->pendingAttachmentIds : null,
        ]);
        Cache::forget('disinfection_slips_all');

        // Log the create action
        Logger::create(
            DisinfectionSlip::class,
            $slip->id,
            "Created disinfection slip {$slip->slip_id}",
            $slip->only(['truck_id', 'destination_id', 'driver_id', 'location_id', 'reason_id', 'status'])
        );

        $this->dispatch('toast', message: 'Disinfection slip created successfully!', type: 'success');        
        
        // Clear pending attachments since they're now attached to the slip
        $this->pendingAttachmentIds = [];
        
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

    public function openAddAttachmentModal()
    {
        if ($this->isUserDisabled()) {
            $this->dispatch('toast', message: 'Your account has been disabled. Please contact an administrator.', type: 'error');
            return;
        }
        $this->showAddAttachmentModal = true;
        $this->dispatch('showAddAttachmentModal');
    }

    public function closeAddAttachmentModal()
    {
        $this->showAddAttachmentModal = false;
    }

    public function uploadAttachment($imageData)
    {
        try {
            // Authorization check: Only allow if location permits slip creation
            if (!$this->canCreateSlip) {
                $this->dispatch('toast', message: 'This location is not authorized to create slips.', type: 'error');
                return;
            }
            
            if ($this->isUserDisabled()) {
                $this->dispatch('toast', message: 'Your account has been disabled. Please contact an administrator.', type: 'error');
                return;
            }

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
            $filename = 'disinfection_slip_pending_' . time() . '_' . Str::random(8) . '.' . $extension;
            
            // Use Storage facade for consistency - save to public disk
            Storage::disk('public')->put('images/uploads/' . $filename, $imageDecoded);

            // Store relative path in database
            $relativePath = 'images/uploads/' . $filename;

            // Create attachment record
            $attachment = Attachment::create([
                'file_path' => $relativePath,
                'user_id' => Auth::id(),
            ]);

            // Add new attachment ID to pending array
            $this->pendingAttachmentIds[] = $attachment->id;

            $totalAttachments = count($this->pendingAttachmentIds);
            $this->dispatch('toast', message: "Photo added ({$totalAttachments} total).", type: 'success');
            Cache::forget('disinfection_slips_all');
        } catch (\Exception $e) {
            Log::error('Attachment upload error: ' . $e->getMessage());
            Cache::forget('disinfection_slips_all');
            $this->dispatch('toast', message: 'Failed to upload photo. Please try again.', type: 'error');
        }
    }

    public function uploadAttachments($imagesData)
    {
        try {
            // Authorization check: Only allow if location permits slip creation
            if (!$this->canCreateSlip) {
                $this->dispatch('toast', message: 'This location is not authorized to create slips.', type: 'error');
                return;
            }
            
            if ($this->isUserDisabled()) {
                $this->dispatch('toast', message: 'Your account has been disabled. Please contact an administrator.', type: 'error');
                return;
            }

            // Validate that imagesData is an array
            if (!is_array($imagesData) || empty($imagesData)) {
                $this->dispatch('toast', message: 'No images provided for upload.', type: 'error');
                return;
            }

            $newAttachmentIds = [];
            $validImages = [];
            $errors = [];

            // Process all images first to validate them
            foreach ($imagesData as $index => $imageData) {
                // Validate image data format
                if (!preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/', $imageData)) {
                    $errors[] = "Image " . ($index + 1) . ": Invalid image format. Only JPEG, PNG, GIF, and WebP are allowed.";
                    continue;
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
                    $errors[] = "Image " . ($index + 1) . ": Failed to decode image data.";
                    continue;
                }

                // Validate file size (15MB max)
                $fileSizeInMB = strlen($imageDecoded) / 1024 / 1024;
                if ($fileSizeInMB > 15) {
                    $errors[] = "Image " . ($index + 1) . ": Image size exceeds 15MB limit.";
                    continue;
                }

                // Validate image using getimagesizefromstring
                $imageInfo = @getimagesizefromstring($imageDecoded);
                if ($imageInfo === false) {
                    $errors[] = "Image " . ($index + 1) . ": Invalid image file. Please capture a valid image.";
                    continue;
                }

                // Validate MIME type
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($imageInfo['mime'], $allowedMimeTypes)) {
                    $errors[] = "Image " . ($index + 1) . ": Invalid image type. Only JPEG, PNG, GIF, and WebP are allowed.";
                    continue;
                }

                // Store valid image data for processing
                $validImages[] = [
                    'decoded' => $imageDecoded,
                    'type' => $imageType,
                ];
            }

            // If there are validation errors, show them and return
            if (!empty($errors)) {
                $errorMessage = implode(' ', $errors);
                $this->dispatch('toast', message: $errorMessage, type: 'error');
                return;
            }

            // If no valid images, return
            if (empty($validImages)) {
                $this->dispatch('toast', message: 'No valid images to upload.', type: 'error');
                return;
            }

            // Process all valid images in a single transaction
            DB::beginTransaction();
            try {
                foreach ($validImages as $image) {
                    // Generate unique filename with correct extension
                    $extension = $image['type'];
                    $filename = 'disinfection_slip_pending_' . time() . '_' . Str::random(8) . '.' . $extension;
                    
                    // Use Storage facade for consistency - save to public disk
                    Storage::disk('public')->put('images/uploads/' . $filename, $image['decoded']);

                    // Store relative path in database
                    $relativePath = 'images/uploads/' . $filename;

                    // Create attachment record
                    $attachment = Attachment::create([
                        'file_path' => $relativePath,
                        'user_id' => Auth::id(),
                    ]);

                    // Add new attachment ID to pending array
                    $newAttachmentIds[] = $attachment->id;
                }

                // Add all new attachment IDs to pending array
                $this->pendingAttachmentIds = array_merge($this->pendingAttachmentIds, $newAttachmentIds);

                // Commit transaction
                DB::commit();

                $totalAttachments = count($this->pendingAttachmentIds);
                $uploadedCount = count($newAttachmentIds);
                $this->dispatch('toast', message: "{$uploadedCount} photo(s) added ({$totalAttachments} total).", type: 'success');
                Cache::forget('disinfection_slips_all');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Batch attachment upload error: ' . $e->getMessage());
            Cache::forget('disinfection_slips_all');
            $this->dispatch('toast', message: 'Failed to upload photos. Please try again.', type: 'error');
        }
    }

    public function confirmRemovePendingAttachment($attachmentId)
    {
        // Authorization check: Only allow if location permits slip creation
        if (!$this->canCreateSlip) {
            $this->dispatch('toast', message: 'This location is not authorized to create slips.', type: 'error');
            return;
        }
        
        $this->pendingAttachmentToDelete = $attachmentId;
        $this->showRemovePendingAttachmentConfirmation = true;
    }

    public function removePendingAttachment()
    {
        try {
            // Authorization check: Only allow if location permits slip creation
            if (!$this->canCreateSlip) {
                $this->dispatch('toast', message: 'This location is not authorized to create slips.', type: 'error');
                return;
            }
            
            if (!$this->pendingAttachmentToDelete) {
                $this->dispatch('toast', message: 'No attachment specified to remove.', type: 'error');
                $this->showRemovePendingAttachmentConfirmation = false;
                return;
            }

            $attachmentId = $this->pendingAttachmentToDelete;
            
            // Find and remove from pending array
            $key = array_search($attachmentId, $this->pendingAttachmentIds);
            if ($key !== false) {
                // Get the attachment record before deletion
                $attachment = Attachment::find($attachmentId, ['id', 'user_id', 'file_path']);
                if ($attachment) {
                    // Check if current user is the one who uploaded this attachment (unless admin/superadmin)
                    $user = Auth::user();
                    $isAdminOrSuperAdmin = in_array($user->user_type, [1, 2]); // 1 = Admin, 2 = SuperAdmin
                    
                    if (!$isAdminOrSuperAdmin && $attachment->user_id !== Auth::id()) {
                        $this->dispatch('toast', message: 'You can only delete attachments that you uploaded.', type: 'error');
                        $this->showRemovePendingAttachmentConfirmation = false;
                        $this->pendingAttachmentToDelete = null;
                        return;
                    }

                    // Delete the physical file from storage
                    if (Storage::disk('public')->exists($attachment->file_path)) {
                        Storage::disk('public')->delete($attachment->file_path);
                    }
                    // Hard delete the attachment record
                    $attachment->forceDelete();
                }

                // Remove from array and re-index
                unset($this->pendingAttachmentIds[$key]);
                $this->pendingAttachmentIds = array_values($this->pendingAttachmentIds); // Re-index array
                
                // Adjust current index after deletion
                $totalAttachments = count($this->pendingAttachmentIds);
                if ($totalAttachments === 0) {
                    // No attachments left, close the modal
                    $this->currentPendingAttachmentIndex = 0;
                    $this->showPendingAttachmentModal = false;
                } else {
                    // Adjust index to stay within bounds
                    // If we deleted the last item, move to the new last item
                    if ($this->currentPendingAttachmentIndex >= $totalAttachments) {
                        $this->currentPendingAttachmentIndex = $totalAttachments - 1;
                    }
                    // If we deleted an item before the current index, no adjustment needed
                    // If we deleted the current item, stay at the same index (which now shows the next item)
                }

                $this->dispatch('toast', message: 'Photo removed.', type: 'success');
            }
            
            $this->showRemovePendingAttachmentConfirmation = false;
            $this->pendingAttachmentToDelete = null;
        } catch (\Exception $e) {
            Log::error('Attachment removal error: ' . $e->getMessage());
            Cache::forget('disinfection_slips_all');
            $this->dispatch('toast', message: 'Failed to remove photo. Please try again.', type: 'error');
            $this->showRemovePendingAttachmentConfirmation = false;
            $this->pendingAttachmentToDelete = null;
        }
    }

    // Pending Attachment Modal Methods
    public function openPendingAttachmentModal($index = 0)
    {
        $this->currentPendingAttachmentIndex = $index;
        $this->showPendingAttachmentModal = true;
    }

    public function closePendingAttachmentModal()
    {
        $this->showPendingAttachmentModal = false;
        $this->currentPendingAttachmentIndex = 0;
    }

    public function nextPendingAttachment()
    {
        $totalAttachments = count($this->pendingAttachmentIds);
        if ($this->currentPendingAttachmentIndex < $totalAttachments - 1) {
            $this->currentPendingAttachmentIndex++;
        }
    }

    public function previousPendingAttachment()
    {
        if ($this->currentPendingAttachmentIndex > 0) {
            $this->currentPendingAttachmentIndex--;
        }
    }

    public function getCurrentPendingAttachmentId()
    {
        $totalAttachments = count($this->pendingAttachmentIds);
        if ($totalAttachments === 0 || $this->currentPendingAttachmentIndex < 0 || $this->currentPendingAttachmentIndex >= $totalAttachments) {
            return null;
        }
        return $this->pendingAttachmentIds[$this->currentPendingAttachmentIndex] ?? null;
    }
    
    /**
     * Get pending attachments collection
     */
    public function getPendingAttachmentsProperty()
    {
        if (empty($this->pendingAttachmentIds)) {
            return collect([]);
        }
        
        return Attachment::whereIn('id', $this->pendingAttachmentIds, 'and', false)->get();
    }
    
    /**
     * Get total count of pending attachments
     */
    public function getTotalPendingAttachmentsProperty()
    {
        return count($this->pendingAttachmentIds);
    }

    public function canDeleteCurrentPendingAttachment()
    {
        $attachmentId = $this->getCurrentPendingAttachmentId();
        if (!$attachmentId) {
            return false;
        }

        $attachment = Attachment::find($attachmentId, ['id', 'user_id']);
        if (!$attachment) {
            return false;
        }

        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $currentRoute = request()->path();
        $isOnUserRoute = str_starts_with($currentRoute, 'user');
        $isAdminOrSuperAdmin = !$isOnUserRoute && in_array($user->user_type ?? 0, [1, 2]); // 1 = Admin, 2 = SuperAdmin
        
        return $isAdminOrSuperAdmin || $attachment->user_id === Auth::id();
    }

    /**
     * Sanitize text input (for textarea fields like remarks_for_disinfection)
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
            // Incoming: Status 2 (In-Transit) - show only unclaimed slips or slips claimed by the current user at destination
            $query->where('destination_id', $location)
                  ->where('location_id', '!=', $location)
                  ->where('status', 2)
                  ->where(function($q) {
                      $q->whereNull('received_guard_id')
                        ->orWhere('received_guard_id', Auth::id());
                  });
        } else {
            // Outgoing: Status 0 (Pending), 1 (Disinfecting), 2 (In-Transit) - only show slips created by the current user
            $query->where('location_id', $location)
                  ->where('hatchery_guard_id', Auth::id())
                  ->whereIn('status', [0, 1, 2]);
        }

        // Then apply other filters
        $slips = $query
            // SEARCH (only search within already filtered type)
            ->when($this->search, function($q) {
                $q->where('slip_id', 'like', '%' . $this->search . '%');
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

            ->with([
                'truck' => function($q) {
                    $q->withTrashed();
                },
                'location',
                'destination',
                'driver',
                'hatcheryGuard',
                'receivedGuard'
            ]) // Eager load all relationships to prevent N+1 queries
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
            'reasons' => $this->isSuperGuard() ? $this->reasons : collect(),
        ]);
    }
    
    // Reason management methods (for super guards - no delete)
    private function getCachedReasons()
    {
        // Only cache id and reason_text to reduce memory usage
        return Cache::remember('reasons_all', 300, function() {
            return Reason::select('id', 'reason_text', 'is_disabled')
                ->orderBy('reason_text', 'asc')
                ->get();
        });
    }
    
    public function loadReasons()
    {
        $reasons = $this->getCachedReasons();
        $this->reasonTexts = $reasons->pluck('reason_text', 'id')->toArray();
    }
    
    public function getReasonsProperty()
    {
        $reasons = $this->getCachedReasons();
        
        // Filter by search term if provided
        if (!empty($this->searchReasonSettings)) {
            $searchTerm = strtolower(trim($this->searchReasonSettings));
            $reasons = $reasons->filter(function($reason) use ($searchTerm) {
                return str_contains(strtolower($reason->reason_text), $searchTerm);
            });
        }
        
        // Convert collection to paginated result for Livewire
        $page = $this->reasonsPage;
        $perPage = 5;
        $items = $reasons->slice(($page - 1) * $perPage, $perPage)->values();
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $reasons->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
    
    // Separate pagination methods for reasons (don't override default pagination)
    public function gotoReasonsPage($page)
    {
        $this->reasonsPage = $page;
    }
    
    public function previousReasonsPage()
    {
        if ($this->reasonsPage > 1) {
            $this->reasonsPage--;
        }
    }
    
    public function nextReasonsPage()
    {
        $this->reasonsPage++;
    }
    
    public function openCreateReasonModal()
    {
        if (!$this->isSuperGuard()) {
            return;
        }
        
        $this->newReasonText = '';
        $this->showCreateReasonModal = true;
    }
    
    public function closeCreateReasonModal()
    {
        $this->newReasonText = '';
        $this->showCreateReasonModal = false;
        $this->resetErrorBag();
    }
    
    public function createReason()
    {
        if (!$this->isSuperGuard()) {
            return;
        }
        
        $this->validate([
            'newReasonText' => [
                'required',
                'string',
                'max:255',
                'min:1',
                function ($attribute, $value, $fail) {
                    $trimmedValue = trim($value);
                    $exists = Reason::whereRaw('LOWER(reason_text) = ?', [strtolower($trimmedValue)], 'and')
                        ->exists();
                    if ($exists) {
                        $fail('This reason already exists.');
                    }
                },
            ],
        ], [], [
            'newReasonText' => 'Reason text',
        ]);
        
        $reason = Reason::create([
            'reason_text' => trim($this->newReasonText),
            'disabled' => false,
        ]);
        
        $this->reasonTexts[$reason->id] = $reason->reason_text;
        
        Logger::create(
            Reason::class,
            $reason->id,
            "Added new reason: {$reason->reason_text}",
            $reason->only(['reason_text', 'is_disabled'])
        );
        
        Cache::forget('reasons_all');
        Cache::forget('reasons_active');
        
        $this->dispatch('toast', message: 'Reason created successfully.', type: 'success');
        $this->closeCreateReasonModal();
        $this->resetPage();
    }
    
    public function startEditingReason($reasonId)
    {
        if (!$this->isSuperGuard()) {
            return;
        }
        
        $reason = Reason::find($reasonId, ['id', 'reason_text']);
        if ($reason) {
            $this->editingReasonId = $reasonId;
            $this->editingReasonText = $reason->reason_text;
            $this->originalReasonText = $reason->reason_text;
        }
    }
    
    public function saveReasonEdit()
    {
        if (!$this->isSuperGuard()) {
            return;
        }
        
        try {
            $this->validate([
                'editingReasonText' => [
                    'required',
                    'string',
                    'max:255',
                    'min:1',
                    function ($attribute, $value, $fail) {
                        $trimmedValue = trim($value);
                        $exists = Reason::where('id', '!=', $this->editingReasonId, 'and')
                            ->whereRaw('LOWER(reason_text) = ?', [strtolower($trimmedValue)], 'and')
                            ->exists();
                        if ($exists) {
                            $fail('This reason already exists.');
                        }
                    },
                ],
            ], [], [
                'editingReasonText' => 'Reason text',
            ]);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $firstError = collect($errors)->flatten()->first();
            if ($firstError) {
                $this->dispatch('toast', message: $firstError, type: 'error');
            }
            throw $e;
        }
        
        if (trim($this->editingReasonText) === $this->originalReasonText) {
            $this->dispatch('toast', message: 'No changes detected.', type: 'info');
            $this->cancelEditing();
            return;
        }
        
        $this->showSaveConfirmation = true;
    }
    
    public function confirmSaveReasonEdit()
    {
        if (!$this->isSuperGuard()) {
            return;
        }
        
        $this->savingReason = true;
        $reason = Reason::find($this->editingReasonId, ['id', 'reason_text', 'is_disabled']);
        
        if ($reason) {
            $oldValues = $reason->only(['reason_text', 'is_disabled']);
            $reason->reason_text = trim($this->editingReasonText);
            $reason->save();
            
            Logger::update(
                Reason::class,
                $reason->id,
                "Updated reason: {$reason->reason_text}",
                $oldValues,
                $reason->only(['reason_text', 'is_disabled'])
            );
            
            $this->reasonTexts[$this->editingReasonId] = $reason->reason_text;
            Cache::forget('reasons_all');
            Cache::forget('reasons_active');
            
            $this->dispatch('toast', message: 'Reason updated successfully.', type: 'success');
        }
        
        $this->showSaveConfirmation = false;
        $this->cancelEditing();
        $this->resetPage();
        $this->savingReason = false;
    }
    
    public function cancelEditing()
    {
        $this->editingReasonId = null;
        $this->editingReasonText = '';
        $this->originalReasonText = '';
    }
    
    public function toggleReasonDisabled($reasonId)
    {
        if (!$this->isSuperGuard()) {
            return;
        }
        
        $reason = Reason::find($reasonId, ['id', 'reason_text', 'is_disabled']);
        if ($reason) {
            $oldValues = $reason->only(['reason_text', 'is_disabled']);
            $reason->disabled = !$reason->disabled;
            $reason->save();
            
            Logger::update(
                Reason::class,
                $reason->id,
                ($reason->disabled ? "Disabled reason: {$reason->reason_text}" : "Enabled reason: {$reason->reason_text}"),
                $oldValues,
                $reason->only(['reason_text', 'is_disabled'])
            );
            
            Cache::forget('reasons_all');
            Cache::forget('reasons_active');
            
            $status = $reason->disabled ? 'disabled' : 'enabled';
            $this->dispatch('toast', message: "Reason {$status} successfully.", type: 'success');
            $this->resetPage();
        }
    }
    
    public function attemptCloseReasonsModal()
    {
        if ($this->editingReasonId !== null) {
            $this->showUnsavedChangesConfirmation = true;
        } else {
            $this->closeReasonsModal();
        }
    }
    
    public function closeWithoutSaving()
    {
        $this->showUnsavedChangesConfirmation = false;
        $this->cancelEditing();
        $this->closeReasonsModal();
    }
    
    public function closeReasonsModal()
    {
        $this->loadReasons();
        $this->newReasonText = '';
        $this->searchReasonSettings = '';
        $this->cancelEditing();
        $this->showReasonsModal = false;
        $this->showSaveConfirmation = false;
        $this->showUnsavedChangesConfirmation = false;
        $this->showDeleteReasonConfirmation = false;
        $this->reasonToDelete = null;
    }
    
    public function openReasonsModal()
    {
        if (!$this->isSuperGuard()) {
            return;
        }
        
        $this->loadReasons();
        $this->newReasonText = '';
        $this->searchReasonSettings = '';
        $this->cancelEditing();
        $this->showReasonsModal = true;
        $this->showSaveConfirmation = false;
        $this->showUnsavedChangesConfirmation = false;
        $this->showDeleteReasonConfirmation = false;
        $this->reasonToDelete = null;
    }
    
    // Delete reason method (not used by super guards, but needed for component compatibility)
    public function confirmDeleteReason($reasonId)
    {
        // Super guards cannot delete reasons
        // This method exists only for component compatibility
        // The delete button is hidden for non-superadmins in the component
    }
}