<?php

namespace App\Livewire\Trucks;

use Livewire\Component;
use App\Models\Location;
use App\Models\DisinfectionSlip;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class LocationCards extends Component
{
    public $search = '';
    
    protected $listeners = ['refreshLocationCards' => '$refresh'];

    public function render()
    {
        // Get all active locations with their attachments
        $locations = Location::where('disabled', false)
            ->with('attachment')
            ->when($this->search, function ($query) {
                $query->where('location_name', 'like', '%' . $this->search . '%');
            })
            ->get();
    
        // Get all ongoing slip counts in a single query for better performance
        // Only query if there are locations to avoid empty whereIn
        // Status 2 (Ongoing) - incoming slips ready for completion
        $ongoingCounts = collect();
        if ($locations->isNotEmpty()) {
            $ongoingCounts = DisinfectionSlip::whereIn('destination_id', $locations->pluck('id'))
                ->whereDate('created_at', today())
                ->where('status', 2) // Ongoing - incoming slips
                ->selectRaw('destination_id, COUNT(*) as count')
                ->groupBy('destination_id')
                ->pluck('count', 'destination_id');
        }
    
        // Map counts to locations
        $locationsWithCounts = $locations->map(function ($location) use ($ongoingCounts) {
            $location->ongoing_count = $ongoingCounts->get($location->id, 0);
            return $location;
        });

        // Get default logo path from settings
        $setting = Setting::where('setting_name', 'default_location_logo')->first();
        $defaultLogoPath = $setting && !empty($setting->value) ? $setting->value : 'images/logo/BGC.png';
    
        return view('livewire.trucks.location-cards', [
            'locations' => $locationsWithCounts,
            'defaultLogoPath' => $defaultLogoPath,
        ]);
    }    
}