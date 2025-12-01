<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\DisinfectionSlip;
use Illuminate\Support\Facades\Session;

class IncomingTrucksCard extends Component
{
    public $count = 0;

    public function mount()
    {
        $this->updateCount();
    }

    public function updateCount()
    {
        $locationId = Session::get('location_id');
        
        if ($locationId) {
            $this->count = DisinfectionSlip::where('destination_id', $locationId)
                ->where('status', 0) // ongoing
                ->whereDate('created_at', today())
                ->count();
        } else {
            $this->count = 0;
        }
    }

    public function render()
    {
        $this->updateCount();
        return view('livewire.incoming-trucks-card');
    }
}

