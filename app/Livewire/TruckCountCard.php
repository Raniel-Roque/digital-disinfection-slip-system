<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\DisinfectionSlip;
use Illuminate\Support\Facades\Session;

class TruckCountCard extends Component
{
    public $count = 0;
    public $type; // 'incoming' or 'outgoing'

    public function mount($type)
    {
        $this->type = $type;
        $this->updateCount();
    }

    public function updateCount()
    {
        $locationId = Session::get('location_id');

        if (!$locationId) {
            $this->count = 0;
            return;
        }

        $query = DisinfectionSlip::query()
            ->whereDate('created_at', today());

        if ($this->type === 'incoming') {
            $query->where('destination_id', $locationId)
            ->where('status', 0);
        } else { // outgoing
            $query->where('destination_id', $locationId)
            ->where('status', [0,1]);
        }

        $this->count = $query->count();
    }

    public function render()
    {
        $this->updateCount();
        return view('livewire.truck-count-card');
    }
}
