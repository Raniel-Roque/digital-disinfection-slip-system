<?php

namespace App\Livewire\Shared\Locations;

use App\Models\Location;
use App\Models\Photo;
use App\Services\Logger;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Create extends Component
{
    use WithFileUploads;

    public $showModal = false;
    public $location_name;
    public $logo;
    public $create_slip = false;

    public $config = ['minUserType' => 2];

    protected $listeners = ['openCreateModal' => 'openModal'];

    public function mount($config = [])
    {
        $this->config = array_merge(['minUserType' => 2], $config);
    }

    public function openModal()
    {
        $this->reset(['location_name', 'logo', 'create_slip']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['location_name', 'logo', 'create_slip']);
        $this->resetValidation();
    }

    public function create()
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

        // Handle logo upload if provided
        $attachmentId = null;
        if ($this->logo) {
            // Generate unique filename
            $extension = $this->logo->getClientOriginalExtension();
            $filename = 'location_logo_' . Str::slug($locationName) . '_' . time() . '_' . Str::random(8) . '.' . $extension;
            
            // Store file in images/logos/ directory
            $path = $this->logo->storeAs('images/logos', $filename, 'public');
            
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
            'create_slip' => $this->create_slip,
        ]);

        Cache::forget('locations_all');
        
        // Log the create action
        Logger::create(
            Location::class,
            $location->id,
            "Created \"{$locationName}\"",
            $location->only(['location_name', 'photo_id', 'disabled'])
        );

        $this->showModal = false;
        $this->reset(['location_name', 'logo', 'create_slip']);
        $this->dispatch('location-created');
        $this->dispatch('toast', message: "{$locationName} has been created.", type: 'success');
    }

    public function clearLogo()
    {
        $this->logo = null;
        $this->resetValidation('logo');
    }

    private function sanitizeAndCapitalizeLocationName($input)
    {
        // Remove extra whitespace
        $input = trim($input);
        
        // Convert to title case (first letter of each word capitalized)
        $input = ucwords(strtolower($input));
        
        return $input;
    }

    public function render()
    {
        return view('livewire.shared.locations.create');
    }
}
