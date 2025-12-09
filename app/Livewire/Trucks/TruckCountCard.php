<?php

namespace App\Livewire\Trucks;

use Livewire\Component;
use App\Models\DisinfectionSlip;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;

class TruckCountCard extends Component
{
    public $type; // 'incoming', 'outgoing', 'total', 'inprogress', 'completed'

    public function mount($type)
    {
        $this->type = $type;
    }

    #[Computed]
    public function count()
    {
        $locationId = Session::get('location_id');

        if (!$locationId) {
            return 0;
        }

        // Base query for all types
        $query = DisinfectionSlip::where(function($q) use ($locationId) {
            $q->where('location_id', $locationId)
              ->orWhere('destination_id', $locationId);
        });

        // Apply filters based on type
        switch ($this->type) {
            case 'incoming':
                // Incoming trucks today (Ongoing status)
                $query->whereDate('created_at', today())
                      ->where('destination_id', $locationId)
                      ->whereIn('status', [0, 1]);
                break;

            case 'outgoing':
                // Outgoing trucks today (Ongoing or disinfecting)
                $query->whereDate('created_at', today())
                      ->where('location_id', $locationId)
                      ->whereIn('status', [0, 1]);
                break;

            case 'total':
                // Total slips today (all statuses)
                $query->whereDate('created_at', today());
                break;

            case 'inprogress':
                // Currently in progress (status 1)
                $query->where('status', 1);
                break;

            case 'completed':
                // Completed today (status 2)
                $query->where('status', 2)
                      ->whereDate('completed_at', today());
                break;
        }

        return $query->count();
    }

    public function render()
    {
        return view('livewire.trucks.truck-count-card');
    }
}