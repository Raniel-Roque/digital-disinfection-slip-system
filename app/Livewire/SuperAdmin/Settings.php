<?php

namespace App\Livewire\Superadmin;

use App\Models\Setting;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\Logger;

class Settings extends Component
{
    use WithFileUploads;

    // Settings values
    public $attachment_retention_days;
    public $default_guard_password;
    public $default_location_logo;
    public $log_retention_months;
    public $resolved_reports_retention_months;
    
    // File upload
    public $default_logo_file;
    public $current_logo_path;

    // Original values for change detection
    public $original_attachment_retention_days;
    public $original_default_guard_password;
    public $original_log_retention_months;
    public $original_resolved_reports_retention_months;

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
        $logRetention = Setting::where('setting_name', 'log_retention_months')->first();
        $resolvedReportsRetention = Setting::where('setting_name', 'resolved_reports_retention_months')->first();

        $this->attachment_retention_days = $attachmentRetention ? $attachmentRetention->value : '30';
        $this->default_guard_password = $defaultPassword ? $defaultPassword->value : 'brookside25';
        $this->default_location_logo = $defaultLogo ? $defaultLogo->value : 'images/logo/BGC.png';
        $this->log_retention_months = $logRetention ? $logRetention->value : '6';
        $this->resolved_reports_retention_months = $resolvedReportsRetention ? $resolvedReportsRetention->value : '6';
        $this->current_logo_path = $this->default_location_logo;
        
        // Store original values for change detection
        $this->original_attachment_retention_days = $this->attachment_retention_days;
        $this->original_default_guard_password = $this->default_guard_password;
        $this->original_log_retention_months = $this->log_retention_months;
        $this->original_resolved_reports_retention_months = $this->resolved_reports_retention_months;
    }

    public function getHasChangesProperty()
    {
        $attachmentChanged = (string)$this->original_attachment_retention_days !== (string)$this->attachment_retention_days;
        $passwordChanged = $this->original_default_guard_password !== $this->default_guard_password;
        $logRetentionChanged = (string)$this->original_log_retention_months !== (string)$this->log_retention_months;
        $resolvedReportsRetentionChanged = (string)$this->original_resolved_reports_retention_months !== (string)$this->resolved_reports_retention_months;
        $logoChanged = $this->default_logo_file !== null;

        return $attachmentChanged || $passwordChanged || $logRetentionChanged || $resolvedReportsRetentionChanged || $logoChanged;
    }
    
    public function clearLogo()
    {
        $this->default_logo_file = null;
        $this->resetValidation('default_logo_file');
        // Update original to current path so change detection works correctly
        $this->original_default_guard_password = $this->default_guard_password;
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
            'log_retention_months' => ['required', 'integer', 'min:1', 'max:120'],
            'resolved_reports_retention_months' => ['required', 'integer', 'min:1', 'max:120'],
            'default_logo_file' => ['nullable', 'image', 'max:2048'], // 2MB max
        ], [
            'attachment_retention_days.required' => 'Attachment retention days is required.',
            'attachment_retention_days.integer' => 'Attachment retention days must be a number.',
            'attachment_retention_days.min' => 'Attachment retention days must be at least 1 day.',
            'attachment_retention_days.max' => 'Attachment retention days cannot exceed 365 days.',
            'default_guard_password.required' => 'Default guard password is required.',
            'default_guard_password.min' => 'Default guard password must be at least 6 characters.',
            'log_retention_months.required' => 'Log retention months is required.',
            'log_retention_months.integer' => 'Log retention months must be a number.',
            'log_retention_months.min' => 'Log retention months must be at least 1 month.',
            'log_retention_months.max' => 'Log retention months cannot exceed 120 months (10 years).',
            'resolved_reports_retention_months.required' => 'Resolved reports retention months is required.',
            'resolved_reports_retention_months.integer' => 'Resolved reports retention months must be a number.',
            'resolved_reports_retention_months.min' => 'Resolved reports retention months must be at least 1 month.',
            'resolved_reports_retention_months.max' => 'Resolved reports retention months cannot exceed 120 months (10 years).',
            'default_logo_file.image' => 'The default logo must be an image file.',
            'default_logo_file.max' => 'The default logo must not be larger than 2MB.',
        ]);

        // Check if there are any changes (excluding logo file which is handled separately)
        $attachmentChanged = (string)$this->original_attachment_retention_days !== (string)$this->attachment_retention_days;
        $passwordChanged = $this->original_default_guard_password !== $this->default_guard_password;
        $logRetentionChanged = (string)$this->original_log_retention_months !== (string)$this->log_retention_months;
        $resolvedReportsRetentionChanged = (string)$this->original_resolved_reports_retention_months !== (string)$this->resolved_reports_retention_months;
        $logoChanged = $this->default_logo_file !== null;

        if (!$attachmentChanged && !$passwordChanged && !$logRetentionChanged && !$resolvedReportsRetentionChanged && !$logoChanged) {
            $this->dispatch('toast', message: 'No changes detected.', type: 'info');
            return;
        }

        // Capture old values for logging
        $oldSettings = [
            'attachment_retention_days' => Setting::where('setting_name', 'attachment_retention_days')->value('value'),
            'default_guard_password' => Setting::where('setting_name', 'default_guard_password')->value('value'),
            'default_location_logo' => Setting::where('setting_name', 'default_location_logo')->value('value'),
            'log_retention_months' => Setting::where('setting_name', 'log_retention_months')->value('value'),
            'resolved_reports_retention_months' => Setting::where('setting_name', 'resolved_reports_retention_months')->value('value'),
        ];
        
        // Update or create settings
        Setting::updateOrCreate(
            ['setting_name' => 'attachment_retention_days'],
            ['value' => (string)$this->attachment_retention_days]
        );

        Setting::updateOrCreate(
            ['setting_name' => 'default_guard_password'],
            ['value' => $this->default_guard_password]
        );

        Setting::updateOrCreate(
            ['setting_name' => 'log_retention_months'],
            ['value' => (string)$this->log_retention_months]
        );

        Setting::updateOrCreate(
            ['setting_name' => 'resolved_reports_retention_months'],
            ['value' => (string)$this->resolved_reports_retention_months]
        );

        // Handle logo file upload
        if ($this->default_logo_file) {
            // Delete old logo if it exists and is not the default seeded one
            $oldLogoPath = $this->default_location_logo;
            if ($oldLogoPath && $oldLogoPath !== 'images/logo/BGC.png' && Storage::disk('public')->exists($oldLogoPath)) {
                Storage::disk('public')->delete($oldLogoPath);
            }
            
            // Store new logo in images/logos/ (plural) directory for user uploads
            // images/logo/ (singular) is reserved for static/seed logos tracked in git
            $logoPath = $this->default_logo_file->store('images/logos', 'public');
            $this->default_location_logo = $logoPath;
            $this->current_logo_path = $logoPath;
            $this->default_logo_file = null;
        }

        Setting::updateOrCreate(
            ['setting_name' => 'default_location_logo'],
            ['value' => $this->default_location_logo]
        );
        
        // Log the settings update
        $newSettings = [
            'attachment_retention_days' => (string)$this->attachment_retention_days,
            'default_guard_password' => $this->default_guard_password,
            'default_location_logo' => $this->default_location_logo,
            'log_retention_months' => (string)$this->log_retention_months,
            'resolved_reports_retention_months' => (string)$this->resolved_reports_retention_months,
        ];
        
        Logger::update(
            Setting::class,
            null,
            "Updated system settings",
            $oldSettings,
            $newSettings
        );

        // Update original values after successful save
        $this->original_attachment_retention_days = (string)$this->attachment_retention_days;
        $this->original_default_guard_password = $this->default_guard_password;
        $this->original_log_retention_months = (string)$this->log_retention_months;
        $this->original_resolved_reports_retention_months = (string)$this->resolved_reports_retention_months;
        
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

