<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\DisinfectionSlip;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;

class TruckCountCard extends Component
{
    public $type; // 'incoming' or 'outgoing'

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

        $query = DisinfectionSlip::whereDate('created_at', today())
            ->where('destination_id', $locationId);

        if ($this->type === 'incoming') {
            $query->where('status', 0);
        } else { // outgoing
            $query->whereIn('status', [0, 1]);
        }

        return $query->count();
    }

    public function render()
    {
        return view('livewire.truck-count-card');
    }
}