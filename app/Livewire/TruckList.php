<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\DisinfectionSlip;
use Illuminate\Support\Facades\Session;

class TruckList extends Component
{
    use WithPagination;

    public $type = 'incoming';
    protected $paginationTheme = 'tailwind'; // use tailwind styles
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
                $q->whereHas('truck', function($t) {
                    $t->where('plate_number', 'like', '%' . $this->search . '%');
                });
            })

            // INCOMING
            ->when($this->type === 'incoming', fn($q) =>
                $q->where('destination_id', $location)
                ->whereIn('status', [0, 1])
            )

            // OUTGOING
            ->when($this->type === 'outgoing', fn($q) =>
                $q->where('location_id', $location)
                ->whereIn('status', [0, 1])
            )

            // COMPLETED
            ->when($this->type === 'completed', fn($q) =>
                $q->where(function($query) use ($location) {
                    $query->where('location_id', $location)
                        ->orWhere('destination_id', $location);
                })->where('status', 2)
            )

            ->orderBy('created_at', 'desc')
            ->paginate(5);

        return view('livewire.truck-list', compact('slips'));
    }

}
