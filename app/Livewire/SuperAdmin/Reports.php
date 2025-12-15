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
    public $filterReportType = null; // null = All, 'slip' = Slip, 'misc' = Miscellaneous
    public $filterCreatedFrom = '';
    public $filterCreatedTo = '';
    
    // Applied filters
    public $appliedResolved = '0'; // Default to Unresolved
    public $appliedReportType = null;
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
    
    // Restore confirmation
    public $showRestoreModal = false;
    public $selectedReportName = null;
    
    // Protection flags
    public $isDeleting = false;
    public $isRestoring = false;
    public $isResolving = false;
    
    // View Details Modal
    public $showDetailsModal = false;
    public $selectedReport = null;
    
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
        $this->appliedReportType = $this->filterReportType;
        $this->appliedCreatedFrom = $this->filterCreatedFrom;
        $this->appliedCreatedTo = $this->filterCreatedTo;
        
        $this->filtersActive = !is_null($this->appliedResolved) || 
                               !is_null($this->appliedReportType) ||
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
            case 'report_type':
                $this->appliedReportType = null;
                $this->filterReportType = null;
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
                               !is_null($this->appliedReportType) ||
                               !empty($this->appliedCreatedFrom) || 
                               !empty($this->appliedCreatedTo);
        
        $this->resetPage();
    }
    
    public function clearFilters()
    {
        $this->filterResolved = null;
        $this->filterReportType = null;
        $this->filterCreatedFrom = '';
        $this->filterCreatedTo = '';
        
        $this->appliedResolved = null;
        $this->appliedReportType = null;
        $this->appliedCreatedFrom = '';
        $this->appliedCreatedTo = '';
        
        $this->filtersActive = false;
        $this->resetPage();
    }
    
    public function openDetailsModal($reportId)
    {
        $this->selectedReport = $this->showDeleted 
            ? Report::onlyTrashed()->with(['user', 'slip', 'resolvedBy'])->findOrFail($reportId)
            : Report::with(['user', 'slip', 'resolvedBy'])->findOrFail($reportId);
        $this->showDetailsModal = true;
    }
    
    public function getReportTypeProperty()
    {
        if (!$this->selectedReport) {
            return null;
        }
        return $this->selectedReport->slip_id ? 'slip' : 'misc';
    }
    
    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedReport = null;
    }
    
    public function closeRestoreModal()
    {
        $this->showRestoreModal = false;
        $this->selectedReportId = null;
        $this->selectedReportName = null;
    }
    
    public function resolveReport()
    {
        // Prevent multiple submissions
        if ($this->isResolving) {
            return;
        }

        $this->isResolving = true;

        try {
            if (!$this->selectedReport) {
                $this->dispatch('toast', message: 'No report selected.', type: 'error');
                return;
            }

        $report = $this->showDeleted 
                ? Report::onlyTrashed()->findOrFail($this->selectedReport->id)
                : Report::findOrFail($this->selectedReport->id);
            $oldValues = [
                'resolved_at' => $report->resolved_at,
                'resolved_by' => $report->resolved_by,
                'description' => $report->description,
            ];
            
        // Atomic update: Only resolve if not already resolved to prevent race conditions
        $updated = Report::where('id', $this->selectedReport->id)
            ->whereNull('resolved_at') // Only update if not already resolved
            ->update([
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ]);
        
        if ($updated === 0) {
            // Report was already resolved by another process
            $report->refresh();
            $this->dispatch('toast', message: 'This report was already resolved by another administrator. Please refresh the page.', type: 'error');
            $this->closeDetailsModal();
            $this->resetPage();
            return;
        }
        
        // Refresh report to get updated data
        $report->refresh();
        
        $reportType = $report->slip_id ? "for slip " . ($report->slip->slip_id ?? 'N/A') : "for misc";
            $newValues = [
                'resolved_at' => $report->resolved_at,
                'resolved_by' => $report->resolved_by,
                'description' => $report->description,
            ];
        Logger::update(
            Report::class,
            $report->id,
            "Resolved report {$reportType}",
            $oldValues,
            $newValues
        );
        
        $this->dispatch('toast', message: 'Report marked as resolved.', type: 'success');
            $this->closeDetailsModal();
        $this->resetPage();
        } finally {
            $this->isResolving = false;
        }
    }

    public function unresolveReport()
    {
        // Prevent multiple submissions
        if ($this->isResolving) {
            return;
        }

        $this->isResolving = true;

        try {
            if (!$this->selectedReport) {
                $this->dispatch('toast', message: 'No report selected.', type: 'error');
                return;
            }

        $report = $this->showDeleted 
                ? Report::onlyTrashed()->findOrFail($this->selectedReport->id)
                : Report::findOrFail($this->selectedReport->id);
        $oldValues = [
            'resolved_at' => $report->resolved_at,
            'resolved_by' => $report->resolved_by,
        ];
        
        // Atomic update: Only unresolve if currently resolved to prevent race conditions
        $updated = Report::where('id', $this->selectedReport->id)
            ->whereNotNull('resolved_at') // Only update if currently resolved
            ->update([
                'resolved_at' => null,
                'resolved_by' => null,
            ]);
        
        if ($updated === 0) {
            // Report was already unresolved by another process
            $report->refresh();
            $this->dispatch('toast', message: 'This report was already unresolved by another administrator. Please refresh the page.', type: 'error');
            $this->closeDetailsModal();
            $this->resetPage();
            return;
        }
        
        // Refresh report to get updated data
        $report->refresh();
        
        $reportType = $report->slip_id ? "for slip " . ($report->slip->slip_id ?? 'N/A') : "for misc";
        $newValues = [
            'resolved_at' => null,
            'resolved_by' => null,
        ];
        Logger::update(
            Report::class,
            $report->id,
            "Unresolved report {$reportType}",
            $oldValues,
            $newValues
        );
        
        $this->dispatch('toast', message: 'Report marked as unresolved.', type: 'success');
            $this->closeDetailsModal();
        $this->resetPage();
        } finally {
            $this->isResolving = false;
        }
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
        
        // Atomic delete: Only delete if not already deleted to prevent race conditions
        $deleted = Report::where('id', $this->selectedReportId)
            ->whereNull('deleted_at') // Only delete if not already deleted
            ->update(['deleted_at' => now()]);
        
        if ($deleted === 0) {
            // Report was already deleted by another process
            $this->showDeleteConfirmation = false;
            $this->reset(['selectedReportId']);
            $this->dispatch('toast', message: 'This report was already deleted by another administrator. Please refresh the page.', type: 'error');
            $this->resetPage();
            return;
        }
        
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
    
    public function openRestoreModal($reportId)
    {
        $report = Report::onlyTrashed()->with(['slip'])->findOrFail($reportId);
        $this->selectedReportId = $reportId;
        $this->selectedReportName = $report->slip_id ? "for slip " . ($report->slip->slip_id ?? 'N/A') : "for misc";
        $this->showRestoreModal = true;
    }

    public function restoreReport()
    {
        // Prevent multiple submissions
        if ($this->isRestoring) {
            return;
        }

        $this->isRestoring = true;

        try {
        if (!$this->selectedReportId) {
            return;
        }

        // Atomic restore: Only restore if currently deleted to prevent race conditions
        // Do the atomic update first, then load the model only if successful
        $restored = Report::onlyTrashed()
            ->where('id', $this->selectedReportId)
            ->update(['deleted_at' => null]);
        
        if ($restored === 0) {
            // Report was already restored or doesn't exist
            $this->showRestoreModal = false;
            $this->reset(['selectedReportId', 'selectedReportName']);
            $this->dispatch('toast', message: 'This report was already restored or does not exist. Please refresh the page.', type: 'error');
            $this->resetPage();
            return;
        }
        
        // Now load the restored report
        $report = Report::with(['slip'])->findOrFail($this->selectedReportId);
        $reportType = $report->slip_id ? "for slip " . ($report->slip->slip_id ?? 'N/A') : "for misc";
        
        Logger::restore(
            Report::class,
            $report->id,
            "Restored report {$reportType}"
        );
        
        $this->showRestoreModal = false;
        $this->reset(['selectedReportId', 'selectedReportName']);
        $this->resetPage();
        $this->dispatch('toast', message: 'Report has been restored.', type: 'success');
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
                $q->whereHas('user', function ($userQuery) use ($searchTerm) {
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
        
        if (!is_null($this->appliedReportType)) {
            if ($this->appliedReportType === 'slip') {
                $query->whereNotNull('slip_id');
            } elseif ($this->appliedReportType === 'misc') {
                $query->whereNull('slip_id');
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
