<?php

namespace App\Livewire\Slips;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Location;
use App\Models\DisinfectionSlip;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class LocationCards extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    public $search = '';
    
    protected $listeners = ['refreshLocationCards' => '$refresh'];
    
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        // Use database pagination instead of loading all locations into memory
        // This is critical for performance with large datasets (e.g., 5,000+ locations)
        $locationsQuery = Location::where('disabled', '=', false, 'and')
            ->with('Photo') // Only eager load photos for current page
            ->orderBy('location_name', 'asc');
        
        // Apply search filter if provided
        if (!empty($this->search)) {
            $locationsQuery->where('location_name', 'like', '%' . $this->search . '%', 'and');
        }
        
        // Paginate the locations - 9 items per page for both mobile and desktop
        $locations = $locationsQuery->paginate(9)->withQueryString();
    
        // Get all in-transit slip counts in a single query for better performance
        // Only query if there are locations to avoid empty whereIn
        // Status 2 (In-Transit) - incoming slips ready for completion
        $inTransitCounts = collect();
        if ($locations->isNotEmpty()) {
            $locationIds = $locations->pluck('id')->toArray();
            if (!empty($locationIds)) {
                $inTransitCounts = DisinfectionSlip::whereIn('destination_id', $locationIds, 'and', false)
                    ->whereDate('created_at', today())
                    ->where('status', '=', 2, 'and') // In-Transit - incoming slips
                    ->selectRaw('destination_id, COUNT(*) as count')
                    ->groupBy('destination_id')
                    ->pluck('count', 'destination_id');
            }
        }
    
        // Map counts to locations - modify items in place to preserve paginator
        $locations->getCollection()->transform(function ($location) use ($inTransitCounts) {
            $location->in_transit_count = $inTransitCounts->get($location->id, 0);
            return $location;
        });

        // Get default logo path from settings (cache this to avoid repeated queries)
        $defaultLogoPath = Cache::remember('default_location_logo', 3600, function () {
            $setting = Setting::where('setting_name', '=', 'default_location_logo', 'and')->first();
            return $setting && !empty($setting->value) ? $setting->value : 'images/logo/BGC.png';
        });
    
        return view('livewire.slips.location-cards', [
            'locations' => $locations,
            'defaultLogoPath' => $defaultLogoPath,
        ]);
    }    
}