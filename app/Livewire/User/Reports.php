<?php

namespace App\Livewire\User;

use App\Models\Report;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class Reports extends Component
{
    use WithPagination;

    public $search = '';
    public $filterReportType = null;
    public $filterResolved = null;
    public $filterCreatedFrom = null;
    public $filterCreatedTo = null;
    public $appliedReportType = null;
    public $appliedResolved = null;
    public $appliedCreatedFrom = null;
    public $appliedCreatedTo = null;
    public $showFilters = false;
    public $showDetailsModal = false;
    public $selectedReport = null;
    public $sortDirection = null; // null, 'asc', 'desc' (applied)
    public $filterSortDirection = null; // null, 'asc', 'desc' (temporary, in filter modal)
    public $filtersActive = false;

    public $availableStatuses = [
        '0' => 'Unresolved',
        '1' => 'Resolved',
    ];

    protected $queryString = ['search'];
    
    protected $listeners = ['modal-closed' => 'clearSelectedReport'];

    public function mount()
    {
        $this->filterResolved = null;
        $this->appliedResolved = null;
        $this->filterSortDirection = $this->sortDirection; // Initialize filter sort with current sort
        $this->checkFiltersActive();
    }

    public function applyFilters()
    {
        $this->appliedReportType = $this->filterReportType;
        $this->appliedResolved = $this->filterResolved;
        $this->appliedCreatedFrom = $this->filterCreatedFrom;
        $this->appliedCreatedTo = $this->filterCreatedTo;
        $this->sortDirection = $this->filterSortDirection;
        $this->checkFiltersActive();
        $this->showFilters = false;
        $this->resetPage();
    }

    public function removeFilter($type)
    {
        if ($type === 'report_type') {
            $this->filterReportType = null;
            $this->appliedReportType = null;
        } elseif ($type === 'resolved') {
            $this->filterResolved = null;
            $this->appliedResolved = null;
        } elseif ($type === 'created_from') {
            $this->filterCreatedFrom = null;
            $this->appliedCreatedFrom = null;
        } elseif ($type === 'created_to') {
            $this->filterCreatedTo = null;
            $this->appliedCreatedTo = null;
        }
        $this->checkFiltersActive();
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filterReportType = null;
        $this->filterResolved = null;
        $this->filterCreatedFrom = null;
        $this->filterCreatedTo = null;
        $this->filterSortDirection = null;
        $this->appliedReportType = null;
        $this->appliedResolved = null;
        $this->appliedCreatedFrom = null;
        $this->appliedCreatedTo = null;
        $this->sortDirection = null;
        $this->checkFiltersActive();
        $this->resetPage();
    }

    public function openDetailsModal($reportId)
    {
        $this->selectedReport = Report::where('user_id', Auth::id())
            ->whereNull('deleted_at')
            ->with(['slip', 'resolvedBy'])
            ->findOrFail($reportId);
        $this->showDetailsModal = true;
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        // Use dispatch to clear selectedReport after modal animation completes
        $this->dispatch('modal-closed');
    }
    
    public function clearSelectedReport()
    {
        $this->selectedReport = null;
    }

    private function checkFiltersActive()
    {
        $this->filtersActive = !is_null($this->appliedReportType) ||
                              (!is_null($this->appliedResolved) && $this->appliedResolved !== '') ||
                              !empty($this->appliedCreatedFrom) ||
                              !empty($this->appliedCreatedTo) ||
                              ($this->sortDirection !== null && $this->sortDirection !== 'desc');
    }

    public function getReportsProperty()
    {
        $query = Report::where('user_id', Auth::id())
            ->whereNull('deleted_at')
            ->with(['slip']);

        // Search
        if (!empty($this->search)) {
            $searchTerm = trim($this->search);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('description', 'like', '%' . $searchTerm . '%')
                  ->orWhere(function ($q2) use ($searchTerm) {
                      $q2->whereNotNull('slip_id')
                         ->whereHas('slip', function ($slipQuery) use ($searchTerm) {
                             $slipQuery->where('slip_id', 'like', '%' . $searchTerm . '%');
                         });
                  });
            });
        }

        // Report Type filter
        if (!is_null($this->appliedReportType)) {
            if ($this->appliedReportType === 'slip') {
                $query->whereNotNull('slip_id');
            } elseif ($this->appliedReportType === 'misc') {
                $query->whereNull('slip_id');
            }
        }

        // Status filter
        if (!is_null($this->appliedResolved) && $this->appliedResolved !== '') {
            if ($this->appliedResolved == '1' || $this->appliedResolved === 1) {
                $query->whereNotNull('resolved_at');
            } else {
                $query->whereNull('resolved_at');
            }
        }

        // Date filters
        if (!empty($this->appliedCreatedFrom)) {
            $query->whereDate('created_at', '>=', $this->appliedCreatedFrom);
        }
        if (!empty($this->appliedCreatedTo)) {
            $query->whereDate('created_at', '<=', $this->appliedCreatedTo);
        }

        // Sorting
        if ($this->sortDirection === 'asc') {
            $query->orderBy('created_at', 'asc');
        } elseif ($this->sortDirection === 'desc') {
            $query->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('created_at', 'desc'); // default
        }

        return $query->paginate(15);
    }

    public function render()
    {
        return view('livewire.user.reports', [
            'reports' => $this->reports,
        ]);
    }
}
