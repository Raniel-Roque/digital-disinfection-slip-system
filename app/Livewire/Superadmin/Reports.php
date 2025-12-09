<?php

namespace App\Livewire\Superadmin;

use App\Models\Report;
use App\Services\Logger;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class Reports extends Component
{
    use WithPagination;

    public $search = '';
    public $showFilters = false;
    public $showDeleted = false;
    
    // Sorting properties
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    
    // Filter properties
    public $filterResolved = '0'; // Default to Unresolved, null = All, 0 = Unresolved, 1 = Resolved
    public $filterCreatedFrom = '';
    public $filterCreatedTo = '';
    
    // Applied filters
    public $appliedResolved = '0'; // Default to Unresolved
    public $appliedCreatedFrom = '';
    public $appliedCreatedTo = '';
    
    public $filtersActive = true; // Default to true since we filter by unresolved
    
    public $availableStatuses = [
        '0' => 'Unresolved',
        '1' => 'Resolved',
    ];
    
    public function mount()
    {
        // Apply default filter on mount
        $this->applyFilters();
    }
    
    // Delete confirmation
    public $showDeleteConfirmation = false;
    public $selectedReportId = null;
    
    // Protection flags
    public $isDeleting = false;
    public $isRestoring = false;
    
    protected $queryString = ['search'];
    
    public function updatedFilterResolved($value)
    {
        if ($value === null || $value === '' || $value === false) {
            $this->filterResolved = null;
        } elseif (is_numeric($value) || $value === '0' || $value === '1') {
            $this->filterResolved = (string)$value;
        } else {
            $this->filterResolved = null;
        }
    }
    
    public function toggleDeletedView()
    {
        $this->showDeleted = !$this->showDeleted;
        $this->resetPage();
    }
    
    public function applySort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }
    
    public function applyFilters()
    {
        $this->appliedResolved = $this->filterResolved;
        $this->appliedCreatedFrom = $this->filterCreatedFrom;
        $this->appliedCreatedTo = $this->filterCreatedTo;
        
        $this->filtersActive = !is_null($this->appliedResolved) || 
                               !empty($this->appliedCreatedFrom) || 
                               !empty($this->appliedCreatedTo);
        
        $this->showFilters = false;
        $this->resetPage();
    }
    
    public function removeFilter($filterName)
    {
        switch ($filterName) {
            case 'resolved':
                $this->appliedResolved = null;
                $this->filterResolved = null;
                break;
            case 'created_from':
                $this->appliedCreatedFrom = '';
                $this->filterCreatedFrom = '';
                break;
            case 'created_to':
                $this->appliedCreatedTo = '';
                $this->filterCreatedTo = '';
                break;
        }
        
        $this->filtersActive = !is_null($this->appliedResolved) || 
                               !empty($this->appliedCreatedFrom) || 
                               !empty($this->appliedCreatedTo);
        
        $this->resetPage();
    }
    
    public function clearFilters()
    {
        $this->filterResolved = null;
        $this->filterCreatedFrom = '';
        $this->filterCreatedTo = '';
        
        $this->appliedResolved = null;
        $this->appliedCreatedFrom = '';
        $this->appliedCreatedTo = '';
        
        $this->filtersActive = false;
        $this->resetPage();
    }
    
    public function resolveReport($reportId)
    {
        $report = $this->showDeleted 
            ? Report::onlyTrashed()->findOrFail($reportId)
            : Report::findOrFail($reportId);
        $oldValues = ['resolved_at' => $report->resolved_at];
        $report->resolved_at = now();
        $report->save();
        
        $reportType = $report->slip_id ? "for slip " . ($report->slip->slip_id ?? 'N/A') : "for misc";
        $newValues = ['resolved_at' => $report->resolved_at];
        Logger::update(
            Report::class,
            $report->id,
            "Resolved report {$reportType}",
            $oldValues,
            $newValues
        );
        
        $this->dispatch('toast', message: 'Report marked as resolved.', type: 'success');
        $this->resetPage();
    }

    public function unresolveReport($reportId)
    {
        $report = $this->showDeleted 
            ? Report::onlyTrashed()->findOrFail($reportId)
            : Report::findOrFail($reportId);
        $oldValues = ['resolved_at' => $report->resolved_at];
        $report->resolved_at = null;
        $report->save();
        
        $reportType = $report->slip_id ? "for slip " . ($report->slip->slip_id ?? 'N/A') : "for misc";
        $newValues = ['resolved_at' => null];
        Logger::update(
            Report::class,
            $report->id,
            "Unresolved report {$reportType}",
            $oldValues,
            $newValues
        );
        
        $this->dispatch('toast', message: 'Report marked as unresolved.', type: 'success');
        $this->resetPage();
    }
    
    public function confirmDelete($reportId)
    {
        $this->selectedReportId = $reportId;
        $this->showDeleteConfirmation = true;
    }
    
    public function deleteReport()
    {
        // Prevent multiple submissions
        if ($this->isDeleting) {
            return;
        }

        $this->isDeleting = true;

        try {
            $report = Report::findOrFail($this->selectedReportId);
        $reportType = $report->slip_id ? "for slip " . ($report->slip->slip_id ?? 'N/A') : "for misc";
        $oldValues = $report->only(['user_id', 'slip_id', 'description', 'resolved_at']);
        
        $report->delete();
        
        Logger::delete(
            Report::class,
            $report->id,
            "Deleted report {$reportType}",
            $oldValues
        );
        
        $this->showDeleteConfirmation = false;
        $this->selectedReportId = null;
        $this->dispatch('toast', message: 'Report has been deleted.', type: 'success');
        $this->resetPage();
        } finally {
            $this->isDeleting = false;
        }
    }
    
    public function restoreReport($reportId)
    {
        // Prevent multiple submissions
        if ($this->isRestoring) {
            return;
        }

        $this->isRestoring = true;

        try {
            $report = Report::onlyTrashed()->findOrFail($reportId);
        $reportType = $report->slip_id ? "for slip " . ($report->slip->slip_id ?? 'N/A') : "for misc";
        $report->restore();
        
        Logger::restore(
            Report::class,
            $report->id,
            "Restored report {$reportType}"
        );
        
        $this->dispatch('toast', message: 'Report has been restored.', type: 'success');
        $this->resetPage();
        } finally {
            $this->isRestoring = false;
        }
    }
    
    private function getFilteredReportsQuery()
    {
        $query = $this->showDeleted 
            ? Report::onlyTrashed()->with(['user', 'slip'])
            : Report::with(['user', 'slip'])->whereNull('deleted_at');
        
        // Search
        if (!empty($this->search)) {
            $searchTerm = trim($this->search);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('description', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                      $userQuery->where('first_name', 'like', '%' . $searchTerm . '%')
                                ->orWhere('last_name', 'like', '%' . $searchTerm . '%');
                  })
                  ->orWhereHas('slip', function ($slipQuery) use ($searchTerm) {
                      $slipQuery->where('slip_id', 'like', '%' . $searchTerm . '%');
                  })
                  ->orWhere(function ($miscQuery) use ($searchTerm) {
                      if (stripos($searchTerm, 'miscellaneous') !== false || stripos($searchTerm, 'misc') !== false) {
                          $miscQuery->whereNull('slip_id');
                      }
                  });
            });
        }
        
        // Filters
        if (!is_null($this->appliedResolved) && $this->appliedResolved !== '') {
            if ($this->appliedResolved == '1' || $this->appliedResolved === 1) {
                $query->whereNotNull('resolved_at');
            } else {
                $query->whereNull('resolved_at');
            }
        }
        
        if (!empty($this->appliedCreatedFrom)) {
            $query->whereDate('created_at', '>=', $this->appliedCreatedFrom);
        }
        
        if (!empty($this->appliedCreatedTo)) {
            $query->whereDate('created_at', '<=', $this->appliedCreatedTo);
        }
        
        // Sorting
        $query->orderBy($this->sortBy, $this->sortDirection);
        
        return $query;
    }
    
    public function render()
    {
        $reports = $this->getFilteredReportsQuery()->paginate(15);
        
        return view('livewire.superadmin.reports', [
            'reports' => $reports,
            'availableStatuses' => $this->availableStatuses,
        ]);
    }
}
