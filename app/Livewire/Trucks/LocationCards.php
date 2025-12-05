<?php

namespace App\Livewire\Trucks;

use Livewire\Component;
use App\Models\Location;
use App\Models\DisinfectionSlip;

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
        $ongoingCounts = collect();
        if ($locations->isNotEmpty()) {
            $ongoingCounts = DisinfectionSlip::whereIn('status', [0, 1])
                ->whereIn('destination_id', $locations->pluck('id'))
                ->whereDate('created_at', today())
                ->selectRaw('destination_id, COUNT(*) as count')
                ->groupBy('destination_id')
                ->pluck('count', 'destination_id');
        }
    
        // Map counts to locations
        $locationsWithCounts = $locations->map(function ($location) use ($ongoingCounts) {
            $location->ongoing_count = $ongoingCounts->get($location->id, 0);
            return $location;
        });
    
        return view('livewire.trucks.location-cards', [
            'locations' => $locationsWithCounts
        ]);
    }    
}