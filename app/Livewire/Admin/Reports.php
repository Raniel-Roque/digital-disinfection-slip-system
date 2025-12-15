<?php

namespace App\Livewire\Admin;

use App\Models\Report;
use App\Models\DisinfectionSlip as DisinfectionSlipModel;
use App\Services\Logger;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class Reports extends Component
{
    use WithPagination;

    public $search = '';
    public $showFilters = false;
    
    // Sorting properties
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    
    // Filter properties
    public $filterReportType = null; // null = All, 'slip' = Slip, 'misc' = Miscellaneous
    public $filterResolved = '0'; // Default to Unresolved, null = All, 0 = Unresolved, 1 = Resolved
    public $filterCreatedFrom = '';
    public $filterCreatedTo = '';
    
    // Applied filters
    public $appliedReportType = null;
    public $appliedResolved = '0'; // Default to Unresolved
    public $appliedCreatedFrom = '';
    public $appliedCreatedTo = '';
    
    public $filtersActive = true; // Default to true since we filter by unresolved
    
    public $availableStatuses = [
        '0' => 'Unresolved',
        '1' => 'Resolved',
    ];
    
    // View Details Modal
    public $showDetailsModal = false;
    public $selectedReport = null;
    
    // Slip Details Modal (reusing showDetailsModal for slip, will be set when slip is selected)
    public $selectedSlip = null;
    public $showAttachmentModal = false;
    public $attachmentFile = null;
    
    // Protection flags
    public $isResolving = false;
    
    public function mount()
    {
        // Apply default filter on mount
        $this->applyFilters();
    }
    
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
        $this->appliedReportType = $this->filterReportType;
        $this->appliedResolved = $this->filterResolved;
        $this->appliedCreatedFrom = $this->filterCreatedFrom;
        $this->appliedCreatedTo = $this->filterCreatedTo;
        
        $this->filtersActive = !is_null($this->appliedReportType) ||
                               !is_null($this->appliedResolved) || 
                               !empty($this->appliedCreatedFrom) || 
                               !empty($this->appliedCreatedTo);
        
        $this->showFilters = false;
        $this->resetPage();
    }
    
    public function removeFilter($filterName)
    {
        switch ($filterName) {
            case 'report_type':
                $this->appliedReportType = null;
                $this->filterReportType = null;
                break;
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
        
        $this->filtersActive = !is_null($this->appliedReportType) ||
                               !is_null($this->appliedResolved) || 
                               !empty($this->appliedCreatedFrom) || 
                               !empty($this->appliedCreatedTo);
        
        $this->resetPage();
    }
    
    public function clearFilters()
    {
        $this->filterReportType = null;
        $this->filterResolved = null;
        $this->filterCreatedFrom = '';
        $this->filterCreatedTo = '';
        
        $this->appliedReportType = null;
        $this->appliedResolved = null;
        $this->appliedCreatedFrom = '';
        $this->appliedCreatedTo = '';
        
        $this->filtersActive = false;
        $this->resetPage();
    }
    
    public function openDetailsModal($reportId)
    {
        $this->selectedReport = Report::with(['user', 'slip', 'resolvedBy'])->findOrFail($reportId);
        $this->showDetailsModal = true;
    }
    
    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedReport = null;
        $this->selectedSlip = null;
        $this->showAttachmentModal = false;
        $this->attachmentFile = null;
    }
    
    public function openSlipDetailsModal($slipId)
    {
        // Close report details modal if open
        $this->showDetailsModal = false;
        $this->selectedReport = null;
        
        $this->selectedSlip = DisinfectionSlipModel::with([
            'truck' => function($q) { $q->withTrashed(); },
            'location',
            'destination',
            'driver',
            'attachment',
            'hatcheryGuard',
            'receivedGuard'
        ])->find($slipId);
        
        $this->showDetailsModal = true;
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

            $report = Report::findOrFail($this->selectedReport->id);
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

            $report = Report::findOrFail($this->selectedReport->id);
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
    
    private function getFilteredReportsQuery()
    {
        $query = Report::with(['user', 'slip'])->whereNull('deleted_at');
        
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
        if (!is_null($this->appliedReportType) && $this->appliedReportType !== '') {
            if ($this->appliedReportType === 'slip') {
                $query->whereNotNull('slip_id');
            } elseif ($this->appliedReportType === 'misc') {
                $query->whereNull('slip_id');
            }
        }
        
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
        
        return view('livewire.admin.reports', [
            'reports' => $reports,
            'availableStatuses' => $this->availableStatuses,
        ]);
    }
    
    // Slip details modal methods (stubs for slip-details-modal component)
    public function canEdit()
    {
        // Reports page cannot edit slips
        return false;
    }
    
    public function openEditModal()
    {
        // Not used in reports context
    }
    
    public function openAttachmentModal($file)
    {
        $this->attachmentFile = $file;
        $this->showAttachmentModal = true;
    }
    
    public function closeAttachmentModal()
    {
        $this->showAttachmentModal = false;
        $this->attachmentFile = null;
    }
    
    public function canRemoveAttachment()
    {
        // Reports page cannot remove attachments
        return false;
    }
    
    public function confirmRemoveAttachment()
    {
        // Not used in reports context
    }
    
    public function removeAttachment()
    {
        // Not used in reports context
    }
}
