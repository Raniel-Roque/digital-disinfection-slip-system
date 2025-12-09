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
        // Status 0 (Ongoing) - no auth required, Status 1 (Disinfecting) - only for auth guard
        $ongoingCounts = collect();
        if ($locations->isNotEmpty()) {
            $ongoingCounts = DisinfectionSlip::whereIn('destination_id', $locations->pluck('id'))
                ->whereDate('created_at', today())
                ->where(function($q) {
                    $q->where('status', 0) // Ongoing - anyone can see
                      ->orWhere(function($q2) {
                          $q2->where('status', 1) // Disinfecting - only for auth guard
                             ->where('received_guard_id', Auth::id());
                      });
                })
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