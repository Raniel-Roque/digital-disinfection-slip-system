<?php

namespace App\Livewire\User\Data;

use App\Models\Location;
use App\Models\Photo;
use App\Models\Setting;
use App\Services\Logger;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Locations extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $showFilters = false;
    
    // Sorting properties
    public $sortColumns = ['location_name' => 'asc']; // Default sort by location_name ascending
    
    // Filter properties
    public $filterStatus = null; // null = All Locations, 0 = Enabled, 1 = Disabled
    public $filterCreatedFrom = '';
    public $filterCreatedTo = '';
    
    // Applied filters
    public $appliedStatus = null; // null = All Locations, 0 = Enabled, 1 = Disabled
    public $appliedCreatedFrom = '';
    public $appliedCreatedTo = '';
    
    public $availableStatuses = [
        0 => 'Enabled',
        1 => 'Disabled',
    ];
    
    // Protection flags
    public $isTogglingStatus = false;
    
    // Ensure filterStatus is properly typed when updated
    public function updatedFilterStatus($value)
    {
        // Handle null, empty string, or numeric values (0, 1)
        // null/empty = All Locations, 0 = Enabled, 1 = Disabled
        // The select will send values as strings, so we convert to int
        if ($value === null || $value === '' || $value === false) {
            $this->filterStatus = null;
        } elseif (is_numeric($value)) {
            $intValue = (int)$value;
            if ($intValue >= 0 && $intValue <= 1) {
                // Store as integer (0 or 1)
                $this->filterStatus = $intValue;
            } else {
                $this->filterStatus = null;
            }
        } else {
            $this->filterStatus = null;
        }
    }
    
    public $selectedLocationId;
    public $selectedLocationDisabled = false;
    public $showEditModal = false;
    public $showDisableModal = false;
    public $showCreateModal = false;

    // Edit form fields
    public $location_name;
    public $edit_logo;
    public $current_logo_path; // Track current logo path for edit
    public $remove_logo = false;
    public $create_slip = false;

    // Create form fields
    public $create_location_name;
    public $create_logo;
    public $create_create_slip = false;

    protected $queryString = ['search'];
    
    public function applySort($column)
    {
        // Initialize sortColumns if it's not an array (for backward compatibility)
        if (!is_array($this->sortColumns)) {
            $this->sortColumns = [];
        }
        
        // If column is already in sort, toggle direction or remove if clicking same direction
        if (isset($this->sortColumns[$column])) {
            if ($this->sortColumns[$column] === 'asc') {
                $this->sortColumns[$column] = 'desc';
            } else {
                // Remove from sort if clicking desc (cycle: asc -> desc -> remove)
                unset($this->sortColumns[$column]);
            }
        } else {
            // Add column with ascending direction
            $this->sortColumns[$column] = 'asc';
        }
        
        // If no sorts remain, default to location_name ascending
        if (empty($this->sortColumns)) {
            $this->sortColumns = ['location_name' => 'asc'];
        }
        
        $this->resetPage();
    }
    
    // Helper method to get sort direction for a column
    public function getSortDirection($column)
    {
        return $this->sortColumns[$column] ?? null;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function applyFilters()
    {
        $this->appliedStatus = $this->filterStatus;
        $this->appliedCreatedFrom = $this->filterCreatedFrom;
        $this->appliedCreatedTo = $this->filterCreatedTo;
        $this->showFilters = false;
        $this->resetPage();
    }

    public function removeFilter($filterName)
    {
        if ($filterName === 'status') {
            $this->appliedStatus = null;
            $this->filterStatus = null;
        } elseif ($filterName === 'createdFrom') {
            $this->appliedCreatedFrom = '';
            $this->filterCreatedFrom = '';
        } elseif ($filterName === 'createdTo') {
            $this->appliedCreatedTo = '';
            $this->filterCreatedTo = '';
        }
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->appliedStatus = null;
        $this->appliedCreatedFrom = '';
        $this->appliedCreatedTo = '';
        $this->filterStatus = null;
        $this->filterCreatedFrom = '';
        $this->filterCreatedTo = '';
        $this->resetPage();
    }

    public $original_location_name;
    public $original_attachment_id;
    public $original_create_slip;

    public function openEditModal($locationId)
    {
        $location = Location::findOrFail($locationId);
        $this->selectedLocationId = $locationId;
        $this->location_name = $location->location_name;
        $this->create_slip = (bool) ($location->create_slip ?? false);
        $this->edit_logo = null;
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
        
        $this->showEditModal = true;
    }

    public function getHasChangesProperty()
    {
        if (!$this->selectedLocationId) {
            return false;
        }

        $locationName = trim($this->location_name ?? '');
        $nameChanged = $this->original_location_name !== $locationName;
        $logoChanged = $this->edit_logo !== null || $this->remove_logo === true;
        $createSlipChanged = $this->original_create_slip !== $this->create_slip;

        return $nameChanged || $logoChanged || $createSlipChanged;
    }

    public function updateLocation()
    {
        // Authorization check - allow super guards OR super admins
        $currentUser = Auth::user();
        if (!(($currentUser->user_type === 0 && $currentUser->super_guard) || $currentUser->user_type === 2)) {
            // Regular guards trying to access super guard features - redirect to landing
            return $this->redirect('/', navigate: true);
        }

        $this->validate([
            'location_name' => ['required', 'string', 'max:255'],
            'edit_logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:15360'], // 15MB max
        ], [
            'location_name.required' => 'Location name is required.',
            'location_name.max' => 'Location name must not exceed 255 characters.',
            'edit_logo.image' => 'The logo must be an image.',
            'edit_logo.mimes' => 'The logo must be a file of type: jpeg, jpg, png, gif, webp.',
            'edit_logo.max' => 'The logo must not be larger than 15MB.',
        ], [
            'location_name' => 'Location Name',
            'edit_logo' => 'Logo',
        ]);

        // Sanitize, trim, and capitalize input
        $locationName = $this->sanitizeAndCapitalizeLocationName($this->location_name);
        
        $location = Location::findOrFail($this->selectedLocationId);
        
        // Handle logo update/removal first to determine new photo_id
        $attachmentId = $location->photo_id;
        
        if ($this->remove_logo) {
            // Remove existing logo if it exists
            if ($attachmentId) {
                $Photo = Photo::find($attachmentId);
                if ($Photo) {
                    // Delete the physical file from storage
                    if (Storage::disk('public')->exists($Photo->file_path)) {
                        Storage::disk('public')->delete($Photo->file_path);
                    }
                    // Hard delete the Photo record
                    $Photo->forceDelete();
                }
            }
            $attachmentId = null;
        } elseif ($this->edit_logo) {
            // Upload new logo
            // Delete old logo if it exists
            if ($attachmentId) {
                $oldAttachment = Photo::find($attachmentId);
                if ($oldAttachment) {
                    if (Storage::disk('public')->exists($oldAttachment->file_path)) {
                        Storage::disk('public')->delete($oldAttachment->file_path);
                    }
                    $oldAttachment->forceDelete();
                }
            }
            
            // Generate unique filename
            $extension = $this->edit_logo->getClientOriginalExtension();
            $filename = 'location_logo_' . Str::slug($locationName) . '_' . time() . '_' . Str::random(8) . '.' . $extension;
            
            // Store file in images/logos/ directory
            $path = $this->edit_logo->storeAs('images/logos', $filename, 'public');
            
            // Create Photo record
            $Photo = Photo::create([
                'file_path' => $path,
                'user_id' => Auth::id(),
            ]);
            
            $attachmentId = $Photo->id;
        }
        
        // Check if there are any changes
        $nameChanged = $location->location_name !== $locationName;
        $attachmentChanged = $location->photo_id !== $attachmentId;
        $createSlipChanged = ($location->create_slip ?? false) !== $this->create_slip;
        
        if (!$nameChanged && !$attachmentChanged && !$createSlipChanged) {
            // Reset logo fields if no changes
            $this->edit_logo = null;
            $this->remove_logo = false;
            $this->dispatch('toast', message: 'No changes detected.', type: 'info');
            return;
        }
        
        // Capture old values for logging
        $oldValues = $location->only(['location_name', 'photo_id', 'create_slip']);
        
        $location->update([
            'location_name' => $locationName,
            'photo_id' => $attachmentId,
            'create_slip' => $this->create_slip,
        ]);

        Cache::forget('locations_all');
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
        
        // Log the update
        $location->refresh();
        $newValues = $location->only(['location_name', 'photo_id']);
        Logger::update(
            Location::class,
            $location->id,
            $description,
            $oldValues,
            $newValues
        );

        $this->showEditModal = false;
        $this->reset(['selectedLocationId', 'location_name', 'edit_logo', 'current_logo_path', 'remove_logo', 'original_location_name', 'original_attachment_id', 'create_slip', 'original_create_slip']);
        $this->dispatch('toast', message: "{$locationName} has been updated.", type: 'success');
    }

    public function clearLogo($type = 'create')
    {
        if ($type === 'create') {
            $this->create_logo = null;
            $this->resetValidation('create_logo');
        } else {
            $this->edit_logo = null;
            $this->resetValidation('edit_logo');
        }
    }
    
    public function getEditLogoPathProperty()
    {
        return $this->current_logo_path;
    }

    public function removeLogo()
    {
        $this->remove_logo = true;
        $this->edit_logo = null;
    }

    public function cancelRemoveLogo()
    {
        $this->remove_logo = false;
    }

    public function openDisableModal($locationId)
    {
        $location = Location::findOrFail($locationId);
        $this->selectedLocationId = $locationId;
        $this->selectedLocationDisabled = $location->disabled;
        $this->showDisableModal = true;
    }

    public function toggleLocationStatus()
    {
        // Prevent multiple submissions
        if ($this->isTogglingStatus) {
            return;
        }

        $this->isTogglingStatus = true;

        try {
        // Authorization check - allow super guards OR super admins
        $currentUser = Auth::user();
        if (!(($currentUser->user_type === 0 && $currentUser->super_guard) || $currentUser->user_type === 2)) {
            // Regular guards trying to access super guard features - redirect to landing
            return $this->redirect('/', navigate: true);
        }

        // Atomic update: Get current status and update atomically to prevent race conditions
        $location = Location::findOrFail($this->selectedLocationId);
        $wasDisabled = $location->disabled;
        $newStatus = !$wasDisabled; // true = disabled, false = enabled
        
        // Atomic update: Only update if the current disabled status matches what we expect
        $updated = Location::where('id', $this->selectedLocationId)
            ->where('disabled', $wasDisabled) // Only update if status hasn't changed
            ->update(['disabled' => $newStatus]);
        
        if ($updated === 0) {
            // Status was changed by another process, refresh and show error
            $location->refresh();
            $this->showDisableModal = false;
            $this->reset(['selectedLocationId', 'selectedLocationDisabled']);
            $this->dispatch('toast', message: 'The location status was changed by another administrator. Please refresh the page.', type: 'error');
            return;
        }
        
        // Refresh location to get updated data
        $location->refresh();

        // Always reset to first page to avoid pagination issues when location disappears/appears from filtered results
        $this->resetPage();
        
        $locationName = $location->location_name;
        $message = !$wasDisabled ? "{$locationName} has been disabled." : "{$locationName} has been enabled.";
        
        Cache::forget('locations_all');

        // Log the status change
        Logger::update(
            Location::class,
            $location->id,
            ucfirst(!$wasDisabled ? 'disabled' : 'enabled') . " location \"{$locationName}\"",
            ['disabled' => $wasDisabled],
            ['disabled' => $newStatus]
        );

        $this->showDisableModal = false;
        $this->reset(['selectedLocationId', 'selectedLocationDisabled']);
        $this->dispatch('toast', message: $message, type: 'success');
        } finally {
            $this->isTogglingStatus = false;
        }
    }

    public function closeModal()
    {
        $this->showEditModal = false;
        $this->showDisableModal = false;
        $this->showCreateModal = false;
        $this->reset(['selectedLocationId', 'selectedLocationDisabled', 'location_name', 'edit_logo', 'remove_logo', 'create_location_name', 'create_logo', 'create_slip', 'create_create_slip', 'original_create_slip']);
        $this->resetValidation();
    }

    /**
     * Get default location logo path from settings table
     * Falls back to hardcoded value if setting doesn't exist
     * 
     * @return string
     */
    public function getDefaultLogoPath()
    {
        $setting = Setting::where('setting_name', 'default_location_logo')->first();
        
        if ($setting && !empty($setting->value)) {
            return $setting->value;
        }
        
        // Fallback to default (shouldn't happen if seeded properly)
        return 'images/logo/BGC.png';
    }

    public function openCreateModal()
    {
        $this->reset(['create_location_name', 'create_logo']);
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function createLocation()
    {
        // Authorization check - allow super guards OR super admins
        $currentUser = Auth::user();
        if (!(($currentUser->user_type === 0 && $currentUser->super_guard) || $currentUser->user_type === 2)) {
            // Regular guards trying to access super guard features - redirect to landing
            return $this->redirect('/', navigate: true);
        }

        $this->validate([
            'create_location_name' => ['required', 'string', 'max:255'],
            'create_logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:15360'], // 15MB max
        ], [
            'create_location_name.required' => 'Location name is required.',
            'create_location_name.max' => 'Location name must not exceed 255 characters.',
            'create_logo.image' => 'The logo must be an image.',
            'create_logo.mimes' => 'The logo must be a file of type: jpeg, jpg, png, gif, webp.',
            'create_logo.max' => 'The logo must not be larger than 15MB.',
        ], [
            'create_location_name' => 'Location Name',
            'create_logo' => 'Logo',
        ]);

        // Sanitize, trim, and capitalize input
        $locationName = $this->sanitizeAndCapitalizeLocationName($this->create_location_name);

        // Handle logo upload if provided
        $attachmentId = null;
        if ($this->create_logo) {
            // Generate unique filename
            $extension = $this->create_logo->getClientOriginalExtension();
            $filename = 'location_logo_' . Str::slug($locationName) . '_' . time() . '_' . Str::random(8) . '.' . $extension;
            
            // Store file in images/logos/ directory
            $path = $this->create_logo->storeAs('images/logos', $filename, 'public');
            
            // Create Photo record
            $Photo = Photo::create([
                'file_path' => $path,
                'user_id' => Auth::id(),
            ]);
            
            $attachmentId = $Photo->id;
        }

        // Create location
        $location = Location::create([
            'location_name' => $locationName,
            'photo_id' => $attachmentId,
            'disabled' => false,
            'create_slip' => $this->create_create_slip,
        ]);
        
        Cache::forget('locations_all');
        
        // Log the creation
        $newValues = $location->only(['location_name', 'photo_id', 'disabled']);
        Logger::create(
            Location::class,
            $location->id,
            "Created \"{$locationName}\"",
            $newValues
        );

        $this->showCreateModal = false;
        $this->reset(['create_location_name', 'create_logo', 'create_create_slip']);
        $this->dispatch('toast', message: "{$locationName} has been created.", type: 'success');
        $this->resetPage();
    }

    public function render()
    {
        $locations = Location::with('Photo')
            ->when($this->search, function ($query) {
                $searchTerm = $this->search;
                
                // Sanitize search term to prevent SQL injection
                $searchTerm = trim($searchTerm);
                $searchTerm = preg_replace('/[%_]/', '', $searchTerm); // Remove LIKE wildcards for safety
                
                if (empty($searchTerm)) {
                    return;
                }
                
                // Escape special characters for LIKE
                $escapedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                
                $query->where('location_name', 'like', '%' . $escapedSearchTerm . '%');
            })
            ->when($this->appliedCreatedFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->appliedCreatedFrom);
            })
            ->when($this->appliedCreatedTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->appliedCreatedTo);
            })
            ->when($this->appliedStatus !== null, function ($query) {
                if ($this->appliedStatus === 0) {
                    // Enabled (disabled = false)
                    $query->where('disabled', false);
                } elseif ($this->appliedStatus === 1) {
                    // Disabled (disabled = true)
                    $query->where('disabled', true);
                }
            })
            // Apply multi-column sorting
            ->when(!empty($this->sortColumns), function($query) {
                // Initialize sortColumns if it's not an array
                if (!is_array($this->sortColumns)) {
                    $this->sortColumns = ['location_name' => 'asc'];
                }
                
                $firstSort = true;
                foreach ($this->sortColumns as $column => $direction) {
                    if ($column === 'created_at' && $firstSort) {
                        // Special handling for created_at when it's the primary sort
                        // First: prioritize recent records (within 5 minutes) over older ones
                        $query->orderByRaw("CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 0 ELSE 1 END")
                            // Second: sort recent records by created_at DESC, older records also by created_at (to avoid NULL sorting issues)
                            ->orderByRaw("CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN created_at ELSE created_at END DESC")
                            ->orderBy('created_at', $direction);
                    } else {
                        $query->orderBy($column, $direction);
                    }
                    $firstSort = false;
                }
            })
            ->when(empty($this->sortColumns), function($query) {
                // Default sort if no sorts are set
                $query->orderBy('location_name', 'asc');
            })
            ->paginate(10);

        $filtersActive = $this->appliedStatus !== null || !empty($this->appliedCreatedFrom) || !empty($this->appliedCreatedTo);

        $defaultLogoPath = $this->getDefaultLogoPath();
        
        // Get current location for edit modal if open
        $currentLocation = null;
        if ($this->showEditModal && $this->selectedLocationId) {
            $currentLocation = Location::with('Photo')->find($this->selectedLocationId);
        }

        return view('livewire.user.data.locations', [
            'locations' => $locations,
            'filtersActive' => $filtersActive,
            'availableStatuses' => $this->availableStatuses,
            'defaultLogoPath' => $defaultLogoPath,
            'currentLocation' => $currentLocation,
        ]);
    }

    public function getExportData()
    {
        return Location::with('Photo')
            ->when($this->search, function ($query) {
                $searchTerm = trim($this->search);
                $searchTerm = preg_replace('/[%_]/', '', $searchTerm);
                if (empty($searchTerm)) {
                    return;
                }
                $escapedSearchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                $query->where('location_name', 'like', '%' . $escapedSearchTerm . '%');
            })
            ->when($this->appliedCreatedFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->appliedCreatedFrom);
            })
            ->when($this->appliedCreatedTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->appliedCreatedTo);
            })
            ->when($this->appliedStatus !== null, function ($query) {
                if ($this->appliedStatus === 0) {
                    $query->where('disabled', false);
                } elseif ($this->appliedStatus === 1) {
                    $query->where('disabled', true);
                }
            })
            ->orderBy('location_name', 'asc')
            ->get();
    }

    public function exportCSV()
    {
        $data = $this->getExportData();
        $filename = 'locations_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'Photo; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['Location Name', 'Status', 'Created Date']);
            
            foreach ($data as $location) {
                $status = $location->disabled ? 'Disabled' : 'Enabled';
                fputcsv($file, [
                    $location->location_name,
                    $status,
                    $location->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function exportExcel()
    {
        $data = $this->getExportData();
        $filename = 'locations_' . date('Y-m-d_His') . '.xls';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'Photo; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['Location Name', 'Status', 'Created Date'], "\t");
            
            foreach ($data as $location) {
                $status = $location->disabled ? 'Disabled' : 'Enabled';
                fputcsv($file, [
                    $location->location_name,
                    $status,
                    $location->created_at->format('Y-m-d H:i:s')
                ], "\t");
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function openPrintView()
    {
        $data = $this->getExportData();
        $exportData = $data->map(function($location) {
            return [
                'location_name' => $location->location_name,
                'disabled' => $location->disabled,
                'created_at' => $location->created_at->toIso8601String(),
            ];
        })->toArray();
        
        $filters = [
            'search' => $this->search,
            'status' => $this->appliedStatus,
            'created_from' => $this->appliedCreatedFrom,
            'created_to' => $this->appliedCreatedTo,
        ];
        
        $sorting = $this->sortColumns ?? ['location_name' => 'asc'];
        
        $token = Str::random(32);
        Session::put("export_data_{$token}", $exportData);
        Session::put("export_filters_{$token}", $filters);
        Session::put("export_sorting_{$token}", $sorting);
        Session::put("export_data_{$token}_expires", now()->addMinutes(10));
        
        $printUrl = route('user.print.locations', ['token' => $token]);
        
        $this->dispatch('open-print-window', ['url' => $printUrl]);
    }

    private function sanitizeAndCapitalizeLocationName($name)
    {
        if (empty($name)) {
            return '';
        }

        // Remove HTML tags and trim whitespace
        $name = strip_tags(trim($name));
        
        // Decode HTML entities (e.g., &amp; becomes &, &#39; becomes ')
        $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove any null bytes and other control characters (except newlines/spaces)
        $name = preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/u', '', $name);
        
        // Normalize whitespace (replace multiple spaces with single space)
        $name = preg_replace('/\s+/', ' ', $name);
        
        // Trim again after normalization
        $name = trim($name);
        
        // Convert to title case (handles multiple words correctly)
        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }
}
