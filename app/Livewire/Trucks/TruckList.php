<?php

namespace App\Livewire\Trucks;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\DisinfectionSlip;
use Illuminate\Support\Facades\Session;

class TruckList extends Component
{
    use WithPagination;

    public $type = 'incoming'; // incoming or outgoing
    protected $paginationTheme = 'tailwind';
    
    public $search = '';
    public $showFilters = false;
        
    public $filterDateFrom;
    public $filterDateTo;
    public $filterStatus = '';
    
    public $appliedDateFrom = null;
    public $appliedDateTo = null;
    public $appliedStatus = '';
    
    public $filtersActive = false;
    
    public $availableStatuses = [
        0 => 'Ongoing',
        1 => 'Disinfecting',
    ];

    public function mount($type = 'incoming')
    {
        $this->type = $type;
        $this->filterDateFrom = now()->format('Y-m-d');
        $this->filterDateTo = now()->format('Y-m-d');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function applyFilters()
    {
        $this->appliedDateFrom = $this->filterDateFrom;
        $this->appliedDateTo = $this->filterDateTo;
        $this->appliedStatus = $this->filterStatus;
        $this->filtersActive = true;
        $this->resetPage();
    }

    public function cancelFilters()
    {
        if ($this->filtersActive) {
            $this->filterDateFrom = $this->appliedDateFrom;
            $this->filterDateTo = $this->appliedDateTo;
            $this->filterStatus = $this->appliedStatus;
        } else {
            $this->filterDateFrom = now()->format('Y-m-d');
            $this->filterDateTo = now()->format('Y-m-d');
            $this->filterStatus = '';
        }
    }

    public function clearFilters()
    {
        $this->filterDateFrom = now()->format('Y-m-d');
        $this->filterDateTo = now()->format('Y-m-d');
        $this->filterStatus = '';
    }
    
    public function render()
    {
        $location = Session::get('location_id');

        // Base query with type filter FIRST
        $query = DisinfectionSlip::query();

        // Apply type-specific filter first (most restrictive)
        if ($this->type === 'incoming') {
            $query->where('destination_id', $location)
                  ->whereIn('status', [0, 1]);
        } else {
            $query->where('location_id', $location)
                  ->whereIn('status', [0, 1]);
        }

        // Then apply other filters
        $slips = $query
            // SEARCH (only search within already filtered type)
            ->when($this->search, function($q) {
                $q->where(function($query) {
                    $query->where('slip_id', 'like', '%' . $this->search . '%')
                          ->orWhereHas('truck', function($t) {
                              $t->where('plate_number', 'like', '%' . $this->search . '%');
                          });
                });
            })
            

            // DATE RANGE FILTER
            ->when($this->filtersActive && $this->appliedDateFrom, function($q) {
                $q->whereDate('created_at', '>=', $this->appliedDateFrom);
            })
            ->when($this->filtersActive && $this->appliedDateTo, function($q) {
                $q->whereDate('created_at', '<=', $this->appliedDateTo);
            })

            // STATUS FILTER 
            ->when($this->filtersActive && $this->appliedStatus !== '', function($q) {
                $q->where('status', $this->appliedStatus);
            })

            ->with('truck') // Load relationship only for final filtered results
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        return view('livewire.trucks.truck-list', [
            'slips' => $slips,
            'availableStatuses' => $this->availableStatuses
        ]);
    }
}