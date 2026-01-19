<?php

namespace App\Livewire\Shared\Slips;

use App\Models\DisinfectionSlip;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\Driver;
use App\Models\User;
use App\Models\Reason;
use App\Models\Photo;
use App\Services\Logger;
use Livewire\Component;
use Livewire\Attributes\Renderless;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Create extends Component
{
    public $showModal = false;
    public $showCancelConfirmation = false;
    public $isCreating = false;
    public $minUserType = 2; // Default for SuperAdmin
    public $useGuardMode = false; // If true, use session location and Auth::id() for hatchery_guard_id

    // Form fields
    public $vehicle_id;
    public $location_id; // Origin (optional if useGuardMode - uses session)
    public $destination_id;
    public $driver_id;
    public $hatchery_guard_id; // Optional if useGuardMode - uses Auth::id()
    public $received_guard_id = null; // Optional receiving guard
    public $reason_id;
    public $remarks_for_disinfection;

    // Photo properties for creation
    public $showAddAttachmentModal = false;
    public $pendingAttachmentIds = []; // Store Photo IDs before slip is created
    public $showRemovePendingAttachmentConfirmation = false;
    public $pendingAttachmentToDelete = null;
    
    // Pending Photo Modal (for viewing pending photos)
    public $showPendingAttachmentModal = false;
    public $currentPendingAttachmentIndex = 0;

    // Search properties for dropdowns
    public $searchOrigin = '';
    public $searchDestination = '';
    public $searchVehicle = '';
    public $searchDriver = '';
    public $searchHatcheryGuard = '';
    public $searchReceivedGuard = '';
    public $searchReason = '';

    protected $listeners = [
        'openCreateModal' => 'openModal',
    ];

    public function mount($config = [])
    {
        $this->minUserType = $config['minUserType'] ?? 2;
        $this->useGuardMode = $config['useGuardMode'] ?? false;
    }
    

    public function openModal()
    {
        // Authorization check
        if (Auth::user()->user_type < $this->minUserType) {
            abort(403, 'Unauthorized action.');
        }

        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        // Clean up pending photos if modal is closed without creating
        if ($this->useGuardMode) {
            $this->cleanupPendingAttachments();
        }
        
        // Check if form has unsaved changes
        if ($this->hasUnsavedChanges()) {
            $this->showCancelConfirmation = true;
        } else {
            $this->resetForm();
            $this->showModal = false;
        }
    }
    
    public function updatedShowModal($value)
    {
        // When modal is closed (set to false) and there are pending photos, clean them up
        if ($value === false && $this->useGuardMode && !empty($this->pendingAttachmentIds)) {
            $this->cleanupPendingAttachments();
        }
    }
    
    public function dehydrate()
    {
        // Clean up pending photos when component is being dehydrated (page navigation, refresh, etc.)
        if ($this->useGuardMode && !empty($this->pendingAttachmentIds)) {
            // Only cleanup if modal is not open (to avoid cleanup during normal operation)
            if (!$this->showModal) {
                $this->cleanupPendingAttachments();
            }
        }
    }

    public function cancelCreate()
    {
        // Clean up pending photos
        if ($this->useGuardMode) {
            $this->cleanupPendingAttachments();
        }
        
        $this->resetForm();
        $this->showCancelConfirmation = false;
        $this->showModal = false;
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
        
        // Optimize: Fetch all photos in one query instead of N queries
        $photos = Photo::whereIn('id', $attachmentIdsToCleanup)->get();
        
        foreach ($photos as $Photo) {
            try {
                // Delete the physical file from storage
                if (Storage::disk('public')->exists($Photo->file_path)) {
                    Storage::disk('public')->delete($Photo->file_path);
                }
                // Hard delete the Photo record
                $Photo->forceDelete();
            } catch (\Exception $e) {
                Log::error('Failed to cleanup Photo ' . $Photo->id . ': ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Clean up orphaned pending photos that are not referenced by any slip
     */
    private function cleanupOrphanedPendingAttachments()
    {
        if (!$this->useGuardMode) {
            return; // Only needed for guard mode
        }
        
        // Find all photos with "pending" in filename that are not referenced by any slip
        $orphanedAttachments = Photo::where('file_path', 'like', 'images/uploads/disinfection_slip_pending_%')
            ->get()
            ->filter(function ($Photo) {
                // Check if this Photo is referenced by any slip
                $isReferenced = DisinfectionSlip::whereJsonContains('photo_ids', $Photo->id)->exists();
                return !$isReferenced;
            });

        foreach ($orphanedAttachments as $Photo) {
            try {
                // Delete the physical file from storage
                if (Storage::disk('public')->exists($Photo->file_path)) {
                    Storage::disk('public')->delete($Photo->file_path);
                }
                // Hard delete the Photo record
                $Photo->forceDelete();
            } catch (\Exception $e) {
                Log::error('Failed to cleanup orphaned pending Photo ' . $Photo->id . ': ' . $e->getMessage());
            }
        }
    }

    public function resetForm()
    {
        $this->vehicle_id = null;
        $this->destination_id = null;
        $this->driver_id = null;
        $this->reason_id = null;
        $this->remarks_for_disinfection = null;
        $this->searchOrigin = '';
        $this->searchDestination = '';
        $this->searchVehicle = '';
        $this->searchDriver = '';
        $this->searchHatcheryGuard = '';
        $this->searchReceivedGuard = '';
        $this->searchReason = '';
        
        // Guard mode: Reset location and hatchery guard, or clear them for admin mode
        if ($this->useGuardMode) {
            $this->location_id = Session::get('location_id');
            $this->hatchery_guard_id = Auth::id();
            // Clean up pending photos before clearing the array
            $this->cleanupPendingAttachments();
        } else {
            $this->location_id = null;
            $this->hatchery_guard_id = null;
        }
        
        $this->received_guard_id = null;
        $this->showAddAttachmentModal = false;
        $this->showRemovePendingAttachmentConfirmation = false;
        $this->pendingAttachmentToDelete = null;
        $this->showPendingAttachmentModal = false;
        $this->currentPendingAttachmentIndex = 0;
        $this->resetErrorBag();
    }

    public function hasUnsavedChanges()
    {
        $hasChanges = !empty($this->vehicle_id) || 
               !empty($this->destination_id) || 
               !empty($this->driver_id) || 
               !empty($this->received_guard_id) || 
               !empty($this->reason_id) ||
               !empty($this->remarks_for_disinfection);
        
        // For guard mode, location_id and hatchery_guard_id are auto-set, so don't count them
        // For admin mode, check them
        if (!$this->useGuardMode) {
            $hasChanges = $hasChanges || !empty($this->location_id) || !empty($this->hatchery_guard_id);
        }
        
        // Check for pending photos
        if ($this->useGuardMode && !empty($this->pendingAttachmentIds)) {
            $hasChanges = true;
        }
        
        return $hasChanges;
    }

    // Watch for changes to location_id or destination_id to prevent same selection
    public function updatedLocationId($value)
    {
        // If destination is the same as origin, clear it
        if ($this->destination_id == $this->location_id) {
            $this->destination_id = null;
        }
        
        // Dispatch event to refresh destination dropdown options
        $this->dispatch('refresh-destination-options');
    }

    public function updatedDestinationId($value)
    {
        // If origin is the same as destination, clear it (only in admin mode)
        if (!$this->useGuardMode && $this->location_id == $this->destination_id) {
            $this->location_id = null;
        }
        
        // Dispatch event to refresh origin dropdown options
        $this->dispatch('refresh-origin-options');
    }

    public function updatedHatcheryGuardId()
    {
        // If receiving guard is the same as hatchery guard, clear it
        if ($this->received_guard_id == $this->hatchery_guard_id) {
            $this->received_guard_id = null;
        }
    }

    public function updatedReceivedGuardId()
    {
        // If receiving guard is set to hatchery guard, clear the hatchery guard
        if ($this->received_guard_id == $this->hatchery_guard_id) {
            $this->hatchery_guard_id = null;
        }
    }

    public function createSlip()
    {
        // Prevent multiple submissions
        if ($this->isCreating) {
            return;
        }

        // Authorization check
        if (Auth::user()->user_type < $this->minUserType) {
            abort(403, 'Unauthorized action.');
        }
        
        // Guard mode: Check if location permits slip creation
        if ($this->useGuardMode && !$this->canCreateSlip) {
            $this->dispatch('toast', message: 'This location is not authorized to create slips.', type: 'error');
            return;
        }
        
        // Guard mode: Check if user is disabled
        if ($this->useGuardMode && $this->isUserDisabled()) {
            $this->dispatch('toast', message: 'Your account has been disabled. Please contact an administrator.', type: 'error');
            return;
        }

        $this->isCreating = true;

        try {
            // Guard mode: Use session location and Auth::id() for hatchery guard
            if ($this->useGuardMode) {
                $currentLocationId = Session::get('location_id');
                $this->location_id = $currentLocationId;
                $this->hatchery_guard_id = Auth::id();
            }
            
            // Build validation rules
            $rules = [
                'vehicle_id' => 'required|exists:vehicles,id',
                'destination_id' => [
                    'required',
                    'exists:locations,id',
                    function ($attribute, $value, $fail) {
                        if ($this->useGuardMode) {
                            $currentLocationId = Session::get('location_id');
                            if ($value == $currentLocationId) {
                                $fail('The destination cannot be the same as the current location.');
                            }
                        } else {
                            if ($value == $this->location_id) {
                                $fail('The destination cannot be the same as the origin.');
                            }
                        }
                    },
                ],
                'driver_id' => 'required|exists:drivers,id',
                'reason_id' => [
                    'required',
                    'exists:reasons,id',
                    function ($attribute, $value, $fail) {
                        if ($value) {
                            $reason = Reason::find($value);
                            if (!$reason || $reason->is_disabled) {
                                $fail('The selected reason is not available.');
                            }
                        }
                    },
                ],
                'remarks_for_disinfection' => 'nullable|string|max:1000',
            ];
            
            // Admin mode: Require location_id and hatchery_guard_id
            if (!$this->useGuardMode) {
                $rules['location_id'] = [
                    'required',
                    'exists:locations,id',
                    function ($attribute, $value, $fail) {
                        if ($value == $this->destination_id) {
                            $fail('The origin cannot be the same as the destination.');
                        }
                    },
                ];
                $rules['hatchery_guard_id'] = [
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
                $rules['received_guard_id'] = [
                    'nullable',
                    'exists:users,id',
                    function ($attribute, $value, $fail) {
                        if ($value && $value == $this->hatchery_guard_id) {
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

            $this->validate($rules, [], [
                'location_id' => 'Origin',
                'destination_id' => 'Destination',
                'vehicle_id' => 'Vehicle',
                'driver_id' => 'Driver',
                'hatchery_guard_id' => 'Hatchery Guard',
                'received_guard_id' => 'Receiving Guard',
                'reason_id' => 'Reason',
                'remarks_for_disinfection' => 'Remarks for Disinfection',
            ]);

            // Sanitize remarks_for_disinfection
            $sanitizedRemarks = $this->sanitizeText($this->remarks_for_disinfection);

            $slip = DisinfectionSlip::create([
                'vehicle_id' => $this->vehicle_id,
                'location_id' => $this->location_id,
                'destination_id' => $this->destination_id,
                'driver_id' => $this->driver_id,
                'hatchery_guard_id' => $this->hatchery_guard_id,
                'received_guard_id' => $this->received_guard_id,
                'reason_id' => $this->reason_id,
                'remarks_for_disinfection' => $sanitizedRemarks,
                'status' => 0, // Pending
                'slip_id' => $this->generateSlipId(),
                'photo_ids' => ($this->useGuardMode && !empty($this->pendingAttachmentIds)) ? $this->pendingAttachmentIds : null,
            ]);
            
            Cache::forget('disinfection_slips_all');

            $slipId = $slip->slip_id;
            
            // Log the create action
            Logger::create(
                DisinfectionSlip::class,
                $slip->id,
                "Created disinfection slip {$slipId}",
                $slip->only([
                    'vehicle_id',
                    'location_id',
                    'destination_id',
                    'driver_id',
                    'hatchery_guard_id',
                    'received_guard_id',
                    'reason_id',
                    'remarks_for_disinfection',
                    'status'
                ])
            );
            
            $this->dispatch('toast', message: "Disinfection slip created successfully!", type: 'success');
            $this->dispatch('slip-created');
            $this->dispatch('refresh-slips'); // Refresh the slip list
            
            // Clear pending photos since they're now attached to the slip
            if ($this->useGuardMode) {
                $this->pendingAttachmentIds = [];
            }
            
            // Close modal and reset form
            $this->resetForm();
            $this->showModal = false;
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
     * Sanitize text input
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
        
        // Normalize multiple newlines to double newline max
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        // Trim whitespace from start and end
        $text = trim($text);
        
        return empty($text) ? null : $text;
    }
    
    // Photo upload methods (only for guard mode)
    public function openAddAttachmentModal()
    {
        if (!$this->useGuardMode) {
            return;
        }
        
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
        if (!$this->useGuardMode) {
            return;
        }
        
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

            // Create Photo record
            $Photo = Photo::create([
                'file_path' => $relativePath,
                'user_id' => Auth::id(),
            ]);

            // Add new Photo ID to pending array
            $this->pendingAttachmentIds[] = $Photo->id;

            $totalAttachments = count($this->pendingAttachmentIds);
            $this->dispatch('toast', message: "Photo added ({$totalAttachments} total).", type: 'success');
            Cache::forget('disinfection_slips_all');
        } catch (\Exception $e) {
            Log::error('Photo upload error: ' . $e->getMessage());
            Cache::forget('disinfection_slips_all');
            $this->dispatch('toast', message: 'Failed to upload photo. Please try again.', type: 'error');
        }
    }

    public function uploadAttachments($imagesData)
    {
        if (!$this->useGuardMode) {
            return;
        }
        
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

                    // Create Photo record
                    $Photo = Photo::create([
                        'file_path' => $relativePath,
                        'user_id' => Auth::id(),
                    ]);

                    // Add new Photo ID to pending array
                    $newAttachmentIds[] = $Photo->id;
                }

                // Add all new Photo IDs to pending array
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
            Log::error('Batch Photo upload error: ' . $e->getMessage());
            Cache::forget('disinfection_slips_all');
            $this->dispatch('toast', message: 'Failed to upload photos. Please try again.', type: 'error');
        }
    }

    public function confirmRemovePendingAttachment($attachmentId)
    {
        if (!$this->useGuardMode) {
            return;
        }
        
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
        if (!$this->useGuardMode) {
            return;
        }
        
        try {
            // Authorization check: Only allow if location permits slip creation
            if (!$this->canCreateSlip) {
                $this->dispatch('toast', message: 'This location is not authorized to create slips.', type: 'error');
                return;
            }
            
            if (!$this->pendingAttachmentToDelete) {
                $this->dispatch('toast', message: 'No Photo specified to remove.', type: 'error');
                $this->showRemovePendingAttachmentConfirmation = false;
                return;
            }

            $attachmentId = $this->pendingAttachmentToDelete;
            
            // Find and remove from pending array
            $key = array_search($attachmentId, $this->pendingAttachmentIds);
            if ($key !== false) {
                // Get the Photo record before deletion
                $Photo = Photo::find($attachmentId, ['id', 'user_id', 'file_path']);
                if ($Photo) {
                    // Check if current user is the one who uploaded this Photo (unless admin/superadmin)
                    $user = Auth::user();
                    $isAdminOrSuperAdmin = in_array($user->user_type, [1, 2]); // 1 = Admin, 2 = SuperAdmin
                    
                    if (!$isAdminOrSuperAdmin && $Photo->user_id !== Auth::id()) {
                        $this->dispatch('toast', message: 'You can only delete photos that you uploaded.', type: 'error');
                        $this->showRemovePendingAttachmentConfirmation = false;
                        $this->pendingAttachmentToDelete = null;
                        return;
                    }

                    // Delete the physical file from storage
                    if (Storage::disk('public')->exists($Photo->file_path)) {
                        Storage::disk('public')->delete($Photo->file_path);
                    }
                    // Hard delete the Photo record
                    $Photo->forceDelete();
                }

                // Remove from array and re-index
                unset($this->pendingAttachmentIds[$key]);
                $this->pendingAttachmentIds = array_values($this->pendingAttachmentIds); // Re-index array
                
                // Adjust current index after deletion
                $totalAttachments = count($this->pendingAttachmentIds);
                if ($totalAttachments === 0) {
                    // No photos left, close the modal
                    $this->currentPendingAttachmentIndex = 0;
                    $this->showPendingAttachmentModal = false;
                } else {
                    // Adjust index to stay within bounds
                    if ($this->currentPendingAttachmentIndex >= $totalAttachments) {
                        $this->currentPendingAttachmentIndex = $totalAttachments - 1;
                    }
                }

                $this->dispatch('toast', message: 'Photo removed.', type: 'success');
            }
            
            $this->showRemovePendingAttachmentConfirmation = false;
            $this->pendingAttachmentToDelete = null;
        } catch (\Exception $e) {
            Log::error('Photo removal error: ' . $e->getMessage());
            Cache::forget('disinfection_slips_all');
            $this->dispatch('toast', message: 'Failed to remove photo. Please try again.', type: 'error');
            $this->showRemovePendingAttachmentConfirmation = false;
            $this->pendingAttachmentToDelete = null;
        }
    }

    // Pending Photo Modal Methods
    public function openPendingAttachmentModal($index = 0)
    {
        if (!$this->useGuardMode) {
            return;
        }
        
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
     * Get pending photos collection
     */
    public function getPendingAttachmentsProperty()
    {
        if (empty($this->pendingAttachmentIds)) {
            return collect([]);
        }

        return Photo::whereIn('id', $this->pendingAttachmentIds)->get();
    }

    /**
     * Check if the current location permits slip creation (for guard mode)
     */
    public function getCanCreateSlipProperty()
    {
        if (!$this->useGuardMode) {
            return true; // Admin mode always allows creation
        }

        $currentLocationId = Session::get('location_id');
        if (!$currentLocationId) {
            return false;
        }

        // Check if the location exists and is not disabled
        $location = Location::find($currentLocationId);
        return $location && !$location->disabled && !$location->trashed();
    }

    /**
     * Check if the current user is disabled
     */
    public function isUserDisabled()
    {
        $user = Auth::user();
        return $user && $user->disabled;
    }
    
    /**
     * Get total count of pending photos
     */
    public function getTotalPendingAttachmentsProperty()
    {
        return count($this->pendingAttachmentIds);
    }

    public function canDeleteCurrentPendingAttachment()
    {
        if (!$this->useGuardMode) {
            return false;
        }
        
        $attachmentId = $this->getCurrentPendingAttachmentId();
        if (!$attachmentId) {
            return false;
        }

        $Photo = Photo::find($attachmentId, ['id', 'user_id']);
        if (!$Photo) {
            return false;
        }

        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $currentRoute = request()->path();
        $isOnUserRoute = str_starts_with($currentRoute, 'user');
        $isAdminOrSuperAdmin = !$isOnUserRoute && in_array($user->user_type ?? 0, [1, 2]); // 1 = Admin, 2 = SuperAdmin
        
        return $isAdminOrSuperAdmin || $Photo->user_id === Auth::id();
    }

    // Paginated data fetching methods for searchable dropdowns
    #[Renderless]
    public function getPaginatedVehicles($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = Vehicle::query()
            ->whereNull('deleted_at')
            ->where('disabled', false)
            ->select(['id', 'vehicle']);

        // Apply search filter
        if (!empty($search)) {
            $query->where('vehicle', 'like', '%' . $search . '%');
        }

        // Include specific IDs (for selected items)
        if (!empty($includeIds)) {
            $includedItems = Vehicle::whereIn('id', $includeIds)
                ->select(['id', 'vehicle'])
                ->orderBy('vehicle', 'asc')
                ->get()
                ->pluck('vehicle', 'id')
                ->toArray();
            return [
                'data' => $includedItems,
                'has_more' => false,
                'total' => count($includedItems),
            ];
        }

        $query->orderBy('vehicle', 'asc');
        
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Get total count for this query
        $total = $query->count();
        
        // Get paginated results
        $results = $query->skip($offset)->take($perPage)->get();
        
        // Convert to array format
        $data = $results->pluck('vehicle', 'id')->toArray();
        
        return [
            'data' => $data,
            'has_more' => ($offset + $perPage) < $total,
            'total' => $total,
        ];
    }

    #[Renderless]
    public function getPaginatedDrivers($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = Driver::query()
            ->whereNull('deleted_at')
            ->where('disabled', false)
            ->select(['id', 'first_name', 'middle_name', 'last_name']);

        // Apply search filter
        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('first_name', 'like', $searchTerm)
                  ->orWhere('middle_name', 'like', $searchTerm)
                  ->orWhere('last_name', 'like', $searchTerm);
            });
        }

        // Include specific IDs (for selected items)
        if (!empty($includeIds)) {
            $includedItems = Driver::whereIn('id', $includeIds)
                ->select(['id', 'first_name', 'middle_name', 'last_name'])
                ->orderBy('first_name', 'asc')
                ->get()
                ->mapWithKeys(function ($driver) {
                    return [$driver->id => trim("{$driver->first_name} {$driver->middle_name} {$driver->last_name}")];
                })
                ->toArray();
            return [
                'data' => $includedItems,
                'has_more' => false,
                'total' => count($includedItems),
            ];
        }

        $query->orderBy('first_name', 'asc');
        
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Get total count for this query
        $total = $query->count();
        
        // Get paginated results
        $results = $query->skip($offset)->take($perPage)->get();
        
        // Convert to array format
        $data = $results->mapWithKeys(function ($driver) {
            return [$driver->id => trim("{$driver->first_name} {$driver->middle_name} {$driver->last_name}")];
        })->toArray();
        
        return [
            'data' => $data,
            'has_more' => ($offset + $perPage) < $total,
            'total' => $total,
        ];
    }

    #[Renderless]
    public function getPaginatedLocations($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = Location::query()
            ->whereNull('deleted_at')
            ->where('disabled', false)
            ->select(['id', 'location_name']);

        // Exclude the opposite selection based on which dropdown is calling this method
        // If we're fetching for the selected item (includeIds), don't apply exclusion
        if (empty($includeIds)) {
            // Check if we need to exclude location_id or destination_id
            // This works because when destination dropdown loads, it should exclude location_id
            // and when location dropdown loads, it should exclude destination_id
            
            // For destination dropdown: exclude origin (location_id)
            if ($this->location_id) {
                $query->where('id', '!=', $this->location_id);
            }
            
            // For origin dropdown: exclude destination (destination_id)
            if ($this->destination_id) {
                $query->where('id', '!=', $this->destination_id);
            }
        }

        // Apply search filter
        if (!empty($search)) {
            $query->where('location_name', 'like', '%' . $search . '%');
        }

        // Include specific IDs (for selected items)
        if (!empty($includeIds)) {
            $includedItems = Location::whereIn('id', $includeIds)
                ->select(['id', 'location_name'])
                ->orderBy('location_name', 'asc')
                ->get()
                ->pluck('location_name', 'id')
                ->toArray();
            return [
                'data' => $includedItems,
                'has_more' => false,
                'total' => count($includedItems),
            ];
        }

        $query->orderBy('location_name', 'asc');
        
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Get total count for this query
        $total = $query->count();
        
        // Get paginated results
        $results = $query->skip($offset)->take($perPage)->get();
        
        // Convert to array format
        $data = $results->pluck('location_name', 'id')->toArray();
        
        return [
            'data' => $data,
            'has_more' => ($offset + $perPage) < $total,
            'total' => $total,
        ];
    }

    #[Renderless]
    public function getPaginatedGuards($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = User::query()
            ->where('user_type', 0)
            ->where('disabled', false)
            ->select(['id', 'first_name', 'middle_name', 'last_name', 'username']);

        // Apply search filter
        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('first_name', 'like', $searchTerm)
                  ->orWhere('middle_name', 'like', $searchTerm)
                  ->orWhere('last_name', 'like', $searchTerm)
                  ->orWhere('username', 'like', $searchTerm);
            });
        }

        // Include specific IDs (for selected items)
        if (!empty($includeIds)) {
            $includedItems = User::whereIn('id', $includeIds)
                ->where('user_type', 0)
                ->select(['id', 'first_name', 'middle_name', 'last_name', 'username'])
                ->orderBy('first_name', 'asc')->orderBy('last_name', 'asc')
                ->get()
                ->mapWithKeys(function ($user) {
                    $name = trim("{$user->first_name} {$user->middle_name} {$user->last_name}");
                    return [$user->id => "{$name} @{$user->username}"];
                })
                ->toArray();
            return [
                'data' => $includedItems,
                'has_more' => false,
                'total' => count($includedItems),
            ];
        }

        $query->orderBy('first_name', 'asc')->orderBy('last_name', 'asc');
        
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Get total count for this query
        $total = $query->count();
        
        // Get paginated results
        $results = $query->skip($offset)->take($perPage)->get();
        
        // Convert to array format
        $data = $results->mapWithKeys(function ($user) {
            $name = trim("{$user->first_name} {$user->middle_name} {$user->last_name}");
            return [$user->id => "{$name} @{$user->username}"];
        })->toArray();
        
        return [
            'data' => $data,
            'has_more' => ($offset + $perPage) < $total,
            'total' => $total,
        ];
    }

    #[Renderless]
    public function getPaginatedReasons($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = Reason::query()
            ->where('is_disabled', false)
            ->select(['id', 'reason_text']);

        // Apply search filter
        if (!empty($search)) {
            $query->where('reason_text', 'like', '%' . $search . '%');
        }

        // Include specific IDs (for selected items)
        if (!empty($includeIds)) {
            $includedItems = Reason::whereIn('id', $includeIds)
                ->select(['id', 'reason_text'])
                ->orderBy('reason_text', 'asc')
                ->get()
                ->pluck('reason_text', 'id')
                ->toArray();
            return [
                'data' => $includedItems,
                'has_more' => false,
                'total' => count($includedItems),
            ];
        }

        $query->orderBy('reason_text', 'asc');
        
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Get total count for this query
        $total = $query->count();
        
        // Get paginated results
        $results = $query->skip($offset)->take($perPage)->get();
        
        // Convert to array format
        $data = $results->pluck('reason_text', 'id')->toArray();
        
        return [
            'data' => $data,
            'has_more' => ($offset + $perPage) < $total,
            'total' => $total,
        ];
    }

    public function render()
    {
        return view('livewire.shared.slips.create');
    }
}
