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
    public $showRemoveAttachmentConfirmation = false;
    public $showReportModal = false;
    public $selectedSlip = null;
    public $attachmentFile = null;
    public $reportDescription = '';

    public $isEditing = false;
    
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

    public function openDetailsModal($id, $type = null)
    {
        // Set the type if provided
        if ($type) {
            $this->type = $type;
        }
        
        $this->selectedSlip = DisinfectionSlipModel::with([
            'truck',
            'location',
            'destination',
            'driver',
            'attachment',
            'hatcheryGuard',
            'receivedGuard'
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

        // Can edit ONLY on OUTGOING
        return $this->type === 'outgoing'
            && Auth::id() === $this->selectedSlip->hatchery_guard_id 
            && $this->selectedSlip->location_id === Session::get('location_id')
            && $this->selectedSlip->status != 2;
    }

    public function canStartDisinfecting()
    {
        if (!$this->selectedSlip || $this->isUserDisabled()) {
            return false;
        }

        $currentLocation = Session::get('location_id');

        // Can start ONLY on INCOMING
        return $this->type === 'incoming'
            && $this->selectedSlip->status == 0 
            && $this->selectedSlip->destination_id === $currentLocation
            && $this->selectedSlip->location_id !== $currentLocation;
    }

    public function canComplete()
    {
        if (!$this->selectedSlip || $this->isUserDisabled()) {
            return false;
        }

        $currentLocation = Session::get('location_id');

        // Can complete ONLY on INCOMING
        return $this->type === 'incoming'
            && $this->selectedSlip->status == 1 
            && Auth::id() === $this->selectedSlip->received_guard_id
            && $this->selectedSlip->destination_id === $currentLocation
            && $this->selectedSlip->location_id !== $currentLocation;
    }

    public function canDelete()
    {
        if (!$this->selectedSlip || $this->isUserDisabled()) {
            return false;
        }

        // Can delete ONLY on OUTGOING
        return $this->type === 'outgoing'
            && Auth::id() === $this->selectedSlip->hatchery_guard_id 
            && $this->selectedSlip->location_id === Session::get('location_id')
            && $this->selectedSlip->status != 2;
    }

    public function canManageAttachment()
    {
        if (!$this->selectedSlip || $this->isUserDisabled()) {
            return false;
        }

        // Can manage attachment ONLY on INCOMING
        return $this->type === 'incoming'
            && Auth::id() === $this->selectedSlip->received_guard_id 
            && $this->selectedSlip->status == 1
            && $this->selectedSlip->destination_id === Session::get('location_id');
    }

    public function editDetailsModal()
    {
        // Authorization check - must be hatchery guard and location must match
        if (!$this->canEdit()) {
            $this->dispatch('toast', message: 'You are not authorized to edit this slip.', type: 'error');
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

        // Update status to 1 (disinfecting) and set received_guard_id
        $this->selectedSlip->update([
            'status' => 1,
            'received_guard_id' => Auth::id(),
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

        $slipId = $this->selectedSlip->slip_id;
        
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

        // Update status to 2 (completed) and set completed_at timestamp
        $this->selectedSlip->update([
            'status' => 2,
            'completed_at' => now(),
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

        $slipId = $this->selectedSlip->slip_id;
        
        $this->showCompleteConfirmation = false;
        $this->dispatch('toast', message: "{$slipId} disinfection has been completed.", type: 'success');
        $this->dispatch('slip-updated');
    }

    public function deleteSlip()
    {
        // Authorization check using canDelete
        if (!$this->canDelete()) {
            $this->dispatch('toast', message: 'You are not authorized to delete this slip.', type: 'error');
            return;
        }

        $slipId = $this->selectedSlip->slip_id;
        
        // Soft delete the slip (sets deleted_at timestamp)
        $this->selectedSlip->delete();
        
        // Close all modals
        $this->showDeleteConfirmation = false;
        $this->showDetailsModal = false;
        
        // Clear selected slip
        $this->selectedSlip = null;
        
        // Show success message
        $this->dispatch('toast', message: "{$slipId} has been deleted.", type: 'success');
        
        // Refresh the parent component list if needed
        $this->dispatch('slip-deleted');
    }

    public function save()
    {
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
            'reason_for_disinfection' => 'required|string|max:500',
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
            'attachment',
            'hatcheryGuard',
            'receivedGuard'
        ]);

        $slipId = $this->selectedSlip->slip_id;
        
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
        if (!$this->selectedSlip) {
            $this->dispatch('toast', message: 'No slip selected.', type: 'error');
            return;
        }
        
        $this->validate([
            'reportDescription' => 'required|string|min:10|max:1000',
        ], [
            'reportDescription.required' => 'Please provide a reason for reporting.',
            'reportDescription.min' => 'The description must be at least 10 characters.',
            'reportDescription.max' => 'The description must not exceed 1000 characters.',
        ]);
        
        try {
            $report = Report::create([
                'user_id' => Auth::id(),
                'slip_id' => $this->selectedSlip->id,
                'description' => $this->reportDescription,
            ]);
            
            $slipId = $this->selectedSlip->slip_id;
            $this->dispatch('toast', message: "Report submitted successfully for slip {$slipId}.", type: 'success');
            
            $this->showReportModal = false;
            $this->reportDescription = '';
        } catch (\Exception $e) {
            Log::error('Failed to create report: ' . $e->getMessage());
            $this->dispatch('toast', message: 'Failed to submit report. Please try again.', type: 'error');
        }
    }
    
    public function closeReportModal()
    {
        $this->showReportModal = false;
        $this->reportDescription = '';
    }

    public function closeDetailsModal()
    {
        // Reset all states when closing
        $this->isEditing = false;
        $this->showCancelConfirmation = false;
        $this->showDeleteConfirmation = false;
        $this->showDisinfectingConfirmation = false;
        $this->showCompleteConfirmation = false;
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

    public function removeAttachment()
    {
        try {
            // Authorization check using canManageAttachment
            if (!$this->canManageAttachment()) {
                $this->dispatch('toast', message: 'You are not authorized to remove attachments.', type: 'error');
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

                $slipId = $this->selectedSlip->slip_id;
                $this->dispatch('toast', message: "{$slipId}'s attachment has been removed.", type: 'success');
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
                $this->dispatch('toast', message: 'You are not authorized to add attachments.', type: 'error');
                return;
            }

            // Check if attachment already exists
            if ($this->selectedSlip->attachment_id) {
                $this->dispatch('toast', message: 'This slip already has an attachment.', type: 'error');
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
            $filename = 'disinfection_slip_' . $this->selectedSlip->slip_id . '_' . time() . '_' . Str::random(8) . '.' . $extension;
            
            // Use Storage facade for consistency - save to public disk
            Storage::disk('public')->put('images/uploads/' . $filename, $imageDecoded);

            // Store relative path in database
            $relativePath = 'images/uploads/' . $filename;

            // Create attachment record
            $attachment = Attachment::create([
                'file_path' => $relativePath,
            ]);

            // Update disinfection slip with attachment_id
            $this->selectedSlip->update([
                'attachment_id' => $attachment->id,
            ]);

            // Refresh the slip with relationships
            $this->selectedSlip->refresh();
            $this->selectedSlip->load('attachment');

            $slipId = $this->selectedSlip->slip_id;
            $this->dispatch('toast', message: "{$slipId}'s attachment has been uploaded.", type: 'success');
            $this->closeAddAttachmentModal();

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