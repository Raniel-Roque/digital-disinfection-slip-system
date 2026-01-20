<?php

namespace App\Livewire\Shared\Locations;

use App\Models\Location;
use App\Models\Photo;
use App\Models\Setting;
use App\Services\Logger;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Edit extends Component
{
    use WithFileUploads;

    public $showModal = false;
    public $locationId;
    public $locationName = '';
    public $location_name;
    public $logo;
    public $current_logo_path;
    public $remove_logo = false;
    public $create_slip = false;

    public $original_location_name;
    public $original_attachment_id;
    public $original_create_slip;

    public $config = ['minUserType' => 2];

    protected $listeners = ['openEditModal' => 'openModal'];

    public function mount($config = [])
    {
        $this->config = array_merge(['minUserType' => 2], $config);
    }

    public function openModal($locationId)
    {
        $location = Location::findOrFail($locationId);
        $this->locationId = $locationId;
        $this->location_name = $location->location_name;
        $this->create_slip = (bool) ($location->create_slip ?? false);
        $this->logo = null;
        $this->remove_logo = false;
        
        // Set current logo path for preview
        if ($location->photo_id && $location->Photo) {
            $this->current_logo_path = $location->Photo->file_path;
        } else {
            $defaultLogo = Setting::where('setting_name', 'default_location_logo')->value('value') ?? 'images/logo/BGC.png';
            $this->current_logo_path = $defaultLogo;
        }
        
        // Store original values for change detection
        $this->original_location_name = $location->location_name;
        $this->original_attachment_id = $location->photo_id;
        $this->original_create_slip = $location->create_slip ?? false;
        
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['locationId', 'locationName', 'location_name', 'logo', 'current_logo_path', 'remove_logo', 'original_location_name', 'original_attachment_id', 'create_slip', 'original_create_slip']);
        $this->resetValidation();
    }

    public function getHasChangesProperty()
    {
        if (!$this->locationId) {
            return false;
        }

        $locationName = trim($this->location_name ?? '');
        $nameChanged = $this->original_location_name !== $locationName;
        $logoChanged = $this->logo !== null || $this->remove_logo === true;
        $createSlipChanged = $this->original_create_slip !== $this->create_slip;

        return $nameChanged || $logoChanged || $createSlipChanged;
    }

    public function update()
    {
        // Authorization check
        $minUserType = $this->config['minUserType'] ?? 2;
        if (Auth::user()->user_type < $minUserType) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'location_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:15360'], // 15MB max
        ], [
            'location_name.required' => 'Location name is required.',
            'location_name.max' => 'Location name must not exceed 255 characters.',
            'logo.image' => 'The logo must be an image.',
            'logo.mimes' => 'The logo must be a file of type: jpeg, jpg, png, gif, webp.',
            'logo.max' => 'The logo must not be larger than 15MB.',
        ], [
            'location_name' => 'Location Name',
            'logo' => 'Logo',
        ]);

        // Sanitize, trim, and capitalize input
        $locationName = $this->sanitizeAndCapitalizeLocationName($this->location_name);

        $location = Location::findOrFail($this->locationId);
        $this->locationName = $locationName;
        
        // Handle logo update/removal first to determine new photo_id
        $attachmentId = $location->photo_id;

        if ($this->logo) {
            // Upload new logo - this takes priority over remove_logo
            // Delete old logo if it exists
            if ($attachmentId) {
                $oldAttachment = Photo::find($attachmentId);
                if ($oldAttachment) {
                    // Log the photo deletion before deleting
                    Logger::delete(
                        Photo::class,
                        $oldAttachment->id,
                        "Deleted old location logo during update: {$oldAttachment->file_path}",
                        $oldAttachment->only(['file_path', 'user_id'])
                    );

                    if (Storage::disk('public')->exists($oldAttachment->file_path)) {
                        Storage::disk('public')->delete($oldAttachment->file_path);
                    }
                    $oldAttachment->forceDelete();
                }
            }

            // Generate unique filename
            $extension = $this->logo->getClientOriginalExtension();
            $filename = 'location_logo_' . Str::slug($locationName) . '_' . time() . '_' . Str::random(8) . '.' . $extension;

            // Store file in images/logos/ directory
            try {
                $path = $this->logo->storeAs('images/logos', $filename, 'public');

                \Log::info('File upload attempt', [
                    'filename' => $filename,
                    'path' => $path,
                    'file_exists' => $path ? Storage::disk('public')->exists($path) : false
                ]);

                // Verify file was stored successfully
                if (!$path || !Storage::disk('public')->exists($path)) {
                    $this->dispatch('toast', message: 'Failed to upload logo file.', type: 'error');
                    return;
                }
            } catch (\Exception $e) {
                \Log::error('File upload failed', ['error' => $e->getMessage()]);
                $this->dispatch('toast', message: 'File upload failed: ' . $e->getMessage(), type: 'error');
                return;
            }

            // Create Photo record
            try {
                $Photo = Photo::create([
                    'file_path' => $path,
                    'user_id' => Auth::id(),
                ]);

                if (!$Photo || !$Photo->id) {
                    // Clean up uploaded file if Photo creation failed
                    Storage::disk('public')->delete($path);
                    $this->dispatch('toast', message: 'Failed to save logo record.', type: 'error');
                    return;
                }

                \Log::info('Photo created successfully', [
                    'photo_id' => $Photo->id,
                    'file_path' => $path,
                    'user_id' => Auth::id()
                ]);

                $attachmentId = $Photo->id;
            } catch (\Exception $e) {
                // Clean up uploaded file if Photo creation failed
                Storage::disk('public')->delete($path);
                \Log::error('Photo creation failed', ['error' => $e->getMessage()]);
                $this->dispatch('toast', message: 'Failed to save logo: ' . $e->getMessage(), type: 'error');
                return;
            }
        } elseif ($this->remove_logo) {
            // Remove existing logo if it exists
            if ($attachmentId) {
                $Photo = Photo::find($attachmentId);
                if ($Photo) {
                    // Log the photo deletion before deleting
                    Logger::delete(
                        Photo::class,
                        $Photo->id,
                        "Deleted location logo: {$Photo->file_path}",
                        $Photo->only(['file_path', 'user_id'])
                    );

                    // Delete the physical file from storage
                    if (Storage::disk('public')->exists($Photo->file_path)) {
                        Storage::disk('public')->delete($Photo->file_path);
                    }
                    // Hard delete the Photo record
                    $Photo->forceDelete();
                }
            }
            $attachmentId = null;
        }
        
        // Check if there are any changes
        $nameChanged = $location->location_name !== $locationName;
        $attachmentChanged = $location->photo_id !== $attachmentId;
        $createSlipChanged = ($location->create_slip ?? false) !== $this->create_slip;

        // Debug logging
        \Log::info('Location update check', [
            'location_id' => $this->locationId,
            'nameChanged' => $nameChanged,
            'attachmentChanged' => $attachmentChanged,
            'old_photo_id' => $location->photo_id,
            'new_photo_id' => $attachmentId,
            'createSlipChanged' => $createSlipChanged
        ]);

        if (!$nameChanged && !$attachmentChanged && !$createSlipChanged) {
            // Reset logo fields if no changes
            $this->logo = null;
            $this->remove_logo = false;
            $this->dispatch('toast', message: 'No changes detected.', type: 'info');
            return;
        }
        
        // Capture old values for logging
        $oldValues = $location->only(['location_name', 'photo_id', 'disabled', 'create_slip']);
        
        $location->update([
            'location_name' => $locationName,
            'photo_id' => $attachmentId,
            'create_slip' => $this->create_slip,
        ]);

        // Verify the update worked
        $location->refresh();
        \Log::info('Location updated', [
            'location_id' => $location->id,
            'photo_id' => $location->photo_id,
            'photo_exists' => $location->photo_id ? Photo::find($location->photo_id) !== null : false
        ]);
        
        // Generate specific description based on what changed
        $descriptionParts = [];
        if ($nameChanged) {
            $descriptionParts[] = "name to \"{$locationName}\"";
        }
        if ($attachmentChanged) {
            if ($attachmentId === null) {
                $descriptionParts[] = "removed logo";
            } else {
                $descriptionParts[] = "updated logo";
            }
        }
        if ($createSlipChanged) {
            $descriptionParts[] = $this->create_slip ? "enabled create slip" : "disabled create slip";
        }
        $description = "Updated " . implode(" and ", $descriptionParts);
        
        // Log the update action
        Logger::update(
            Location::class,
            $location->id,
            $description,
            $oldValues,
            ['location_name' => $locationName, 'photo_id' => $attachmentId]
        );

        Cache::forget('locations_all');

        $this->showModal = false;
        $this->reset(['locationId', 'location_name', 'logo', 'current_logo_path', 'remove_logo', 'original_location_name', 'original_attachment_id', 'create_slip', 'original_create_slip']);
        $this->dispatch('location-updated');
        $this->dispatch('toast', message: "{$this->locationName} has been updated.", type: 'success');
    }

    public function clearLogo()
    {
        $this->logo = null;
        $this->resetValidation('logo');
    }

    public function getEditLogoPathProperty()
    {
        return $this->current_logo_path;
    }

    public function removeLogo()
    {
        $this->remove_logo = true;
        $this->logo = null;
    }

    public function cancelRemoveLogo()
    {
        $this->remove_logo = false;
    }

    public function getDefaultLogoPath()
    {
        $setting = Setting::where('setting_name', 'default_location_logo')->first();
        
        if ($setting && !empty($setting->value)) {
            return $setting->value;
        }
        
        return 'images/logo/BGC.png';
    }

    private function sanitizeAndCapitalizeLocationName($input)
    {
        $input = trim($input);
        $input = ucwords(strtolower($input));
        return $input;
    }

    public function render()
    {
        $defaultLogoPath = $this->getDefaultLogoPath();
        return view('livewire.shared.locations.edit', [
            'defaultLogoPath' => $defaultLogoPath,
        ]);
    }
}
