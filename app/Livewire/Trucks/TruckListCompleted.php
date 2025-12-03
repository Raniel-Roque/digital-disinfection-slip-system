<?php

namespace App\Livewire\Trucks;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\DisinfectionSlip;
use Illuminate\Support\Facades\Session;

class TruckListCompleted extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';
    
    public $search = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }
    
    public function render()
    {
        $location = Session::get('location_id');

        $slips = DisinfectionSlip::with('truck')
            // SEARCH
            ->when($this->search, function($q) {
                $q->where(function($query) {
                    $query->where('slip_id', 'like', '%' . $this->search . '%')
                          ->orWhereHas('truck', function($t) {
                              $t->where('plate_number', 'like', '%' . $this->search . '%');
                          });
                });
            })
            

            // COMPLETED ONLY
            ->where(function($query) use ($location) {
                $query->where('location_id', $location)
                    ->orWhere('destination_id', $location);
            })
            ->where('status', 2)

            ->orderBy('completed_at', 'desc')
            ->paginate(10);

        return view('livewire.trucks.truck-list-completed', [
            'slips' => $slips
        ]);
    }
}