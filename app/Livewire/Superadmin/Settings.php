<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Setting;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Settings extends Component
{
    use WithFileUploads;

    // Settings values
    public $attachment_retention_days;
    public $default_guard_password;
    public $default_location_logo;
    
    // File upload
    public $default_logo_file;
    public $current_logo_path;

    public function mount()
    {
        // Load current settings
        $this->loadSettings();
    }

    public function loadSettings()
    {
        $attachmentRetention = Setting::where('setting_name', 'attachment_retention_days')->first();
        $defaultPassword = Setting::where('setting_name', 'default_guard_password')->first();
        $defaultLogo = Setting::where('setting_name', 'default_location_logo')->first();

        $this->attachment_retention_days = $attachmentRetention ? $attachmentRetention->value : '30';
        $this->default_guard_password = $defaultPassword ? $defaultPassword->value : 'brookside25';
        $this->default_location_logo = $defaultLogo ? $defaultLogo->value : 'images/logo/BGC.png';
        $this->current_logo_path = $this->default_location_logo;
    }
    
    public function clearLogo()
    {
        $this->default_logo_file = null;
        $this->resetValidation('default_logo_file');
    }

    public function updateSettings()
    {
        // Authorization check
        if (Auth::user()->user_type < 2) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'attachment_retention_days' => ['required', 'integer', 'min:1', 'max:365'],
            'default_guard_password' => ['required', 'string', 'min:6', 'max:255'],
            'default_logo_file' => ['nullable', 'image', 'max:2048'], // 2MB max
        ], [
            'attachment_retention_days.required' => 'Attachment retention days is required.',
            'attachment_retention_days.integer' => 'Attachment retention days must be a number.',
            'attachment_retention_days.min' => 'Attachment retention days must be at least 1 day.',
            'attachment_retention_days.max' => 'Attachment retention days cannot exceed 365 days.',
            'default_guard_password.required' => 'Default guard password is required.',
            'default_guard_password.min' => 'Default guard password must be at least 6 characters.',
            'default_logo_file.image' => 'The default logo must be an image file.',
            'default_logo_file.max' => 'The default logo must not be larger than 2MB.',
        ]);

        // Update or create settings
        Setting::updateOrCreate(
            ['setting_name' => 'attachment_retention_days'],
            ['value' => (string)$this->attachment_retention_days]
        );

        Setting::updateOrCreate(
            ['setting_name' => 'default_guard_password'],
            ['value' => $this->default_guard_password]
        );

        // Handle logo file upload
        if ($this->default_logo_file) {
            // Delete old logo if it exists and is not the default seeded one
            $oldLogoPath = $this->default_location_logo;
            if ($oldLogoPath && $oldLogoPath !== 'images/logo/BGC.png' && Storage::disk('public')->exists($oldLogoPath)) {
                Storage::disk('public')->delete($oldLogoPath);
            }
            
            // Store new logo
            $logoPath = $this->default_logo_file->store('images/logo', 'public');
            $this->default_location_logo = $logoPath;
            $this->current_logo_path = $logoPath;
            $this->default_logo_file = null;
        }

        Setting::updateOrCreate(
            ['setting_name' => 'default_location_logo'],
            ['value' => $this->default_location_logo]
        );

        $this->dispatch('toast', message: 'Settings have been updated successfully.', type: 'success');
    }
    
    public function getDefaultLogoPathProperty()
    {
        return $this->current_logo_path;
    }

    public function render()
    {
        return view('livewire.superadmin.settings');
    }
}

