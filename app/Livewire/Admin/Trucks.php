<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\DisinfectionSlip;
use Livewire\WithPagination;

class Trucks extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $slips = DisinfectionSlip::with(['truck', 'location', 'destination'])
            ->when($this->search, function($query) {
                $query->where('slip_id', 'like', '%' . $this->search . '%')
                    ->orWhereHas('truck', function($q) {
                        $q->where('plate_number', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('location', function($q) {
                        $q->where('location_name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('destination', function($q) {
                        $q->where('location_name', 'like', '%' . $this->search . '%');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.admin.trucks', [
            'slips' => $slips
        ]);
    }
}