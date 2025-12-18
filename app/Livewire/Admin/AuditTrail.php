<?php

namespace App\Livewire\Admin;

use App\Models\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class AuditTrail extends Component
{
    use WithPagination;

    public $search = '';
    public $showFilters = false;
    
    // Sorting properties
    public $sortColumns = ['created_at' => 'desc']; // Default sort by created_at descending
    
    // Filter properties
    public $filterAction = [];
    public $filterModelType = [];
    public $filterUserType = null;
    public $filterCreatedFrom = '';
    public $filterCreatedTo = '';
    
    // Search properties for filters
    public $searchFilterAction = '';
    public $searchFilterModelType = '';
    
    // Applied filters
    public $appliedAction = [];
    public $appliedModelType = [];
    public $appliedUserType = null;
    public $appliedCreatedFrom = '';
    public $appliedCreatedTo = '';
    
    public $filtersActive = false;
    
    public $availableActions = [
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'restore' => 'Restore',
    ];
    
    public $availableModelTypes = [
        'App\\Models\\DisinfectionSlip' => 'Disinfection Slip',
        'App\\Models\\User' => 'User',
        'App\\Models\\Driver' => 'Driver',
        'App\\Models\\Location' => 'Location',
        'App\\Models\\Truck' => 'Truck',
        'App\\Models\\Setting' => 'Setting',
        'App\\Models\\Report' => 'Report',
    ];
    
    public $availableUserTypes = [
        0 => 'Guard',
        1 => 'Admin',
    ];
    
    protected $queryString = ['search'];
    
    public function mount()
    {
        // Initialize array filters
        $this->filterAction = [];
        $this->filterModelType = [];
        $this->appliedAction = [];
        $this->appliedModelType = [];
    }
    
    public function applySort($column)
    {
        if (!is_array($this->sortColumns)) {
            $this->sortColumns = [];
        }
        
        // Toggle sort direction
        if (isset($this->sortColumns[$column])) {
            $currentDir = $this->sortColumns[$column];
            if ($currentDir === 'asc') {
                $this->sortColumns[$column] = 'desc';
            } else {
                unset($this->sortColumns[$column]);
            }
        } else {
            $this->sortColumns[$column] = 'asc';
        }
        
        // Ensure at least one sort column
        if (empty($this->sortColumns)) {
            $this->sortColumns = ['created_at' => 'desc'];
        }
        
        $this->resetPage();
    }
    
    public function getSortDirection($column)
    {
        return $this->sortColumns[$column] ?? null;
    }
    
    public function applyFilters()
    {
        $this->appliedAction = $this->filterAction;
        $this->appliedModelType = $this->filterModelType;
        $this->appliedUserType = $this->filterUserType;
        $this->appliedCreatedFrom = $this->filterCreatedFrom;
        $this->appliedCreatedTo = $this->filterCreatedTo;
        
        $this->filtersActive = !empty($this->appliedAction) || 
                               !empty($this->appliedModelType) || 
                               !is_null($this->appliedUserType) || 
                               !empty($this->appliedCreatedFrom) || 
                               !empty($this->appliedCreatedTo);
        
        $this->showFilters = false;
        $this->resetPage();
    }
    
    public function removeFilter($filterName)
    {
        switch ($filterName) {
            case 'action':
                $this->appliedAction = [];
                $this->filterAction = [];
                break;
            case 'model_type':
                $this->appliedModelType = [];
                $this->filterModelType = [];
                break;
            case 'user_type':
                $this->appliedUserType = null;
                $this->filterUserType = null;
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
        
        $this->filtersActive = !empty($this->appliedAction) || 
                               !empty($this->appliedModelType) || 
                               !is_null($this->appliedUserType) || 
                               !empty($this->appliedCreatedFrom) || 
                               !empty($this->appliedCreatedTo);
        
        $this->resetPage();
    }
    
    public function removeSpecificFilter($filterName, $value)
    {
        switch ($filterName) {
            case 'action':
                $this->appliedAction = array_values(array_filter($this->appliedAction, fn($v) => $v !== $value));
                $this->filterAction = array_values(array_filter($this->filterAction, fn($v) => $v !== $value));
                break;
            case 'model_type':
                $this->appliedModelType = array_values(array_filter($this->appliedModelType, fn($v) => $v !== $value));
                $this->filterModelType = array_values(array_filter($this->filterModelType, fn($v) => $v !== $value));
                break;
        }
        
        $this->filtersActive = !empty($this->appliedAction) || 
                               !empty($this->appliedModelType) || 
                               !is_null($this->appliedUserType) || 
                               !empty($this->appliedCreatedFrom) || 
                               !empty($this->appliedCreatedTo);
        
        $this->resetPage();
    }
    
    public function clearFilters()
    {
        $this->filterAction = [];
        $this->filterModelType = [];
        $this->filterUserType = null;
        $this->filterCreatedFrom = '';
        $this->filterCreatedTo = '';
        $this->searchFilterAction = '';
        $this->searchFilterModelType = '';
        
        $this->appliedAction = [];
        $this->appliedModelType = [];
        $this->appliedUserType = null;
        $this->appliedCreatedFrom = '';
        $this->appliedCreatedTo = '';
        
        $this->filtersActive = false;
        $this->resetPage();
    }
    
    // Computed properties for filtered options with search
    public function getFilterActionOptionsProperty()
    {
        $allOptions = $this->availableActions;
        $options = $allOptions;
        
        if (!empty($this->searchFilterAction)) {
            $searchTerm = strtolower($this->searchFilterAction);
            $options = array_filter($options, function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            }, ARRAY_FILTER_USE_BOTH);
            
            // Ensure selected values are always included
            foreach ($this->filterAction as $selectedValue) {
                if (isset($allOptions[$selectedValue]) && !isset($options[$selectedValue])) {
                    $options[$selectedValue] = $allOptions[$selectedValue];
                }
            }
        }
        
        return $options;
    }
    
    public function getFilterModelTypeOptionsProperty()
    {
        $allOptions = $this->availableModelTypes;
        $options = $allOptions;
        
        if (!empty($this->searchFilterModelType)) {
            $searchTerm = strtolower($this->searchFilterModelType);
            $options = array_filter($options, function ($label) use ($searchTerm) {
                return str_contains(strtolower($label), $searchTerm);
            }, ARRAY_FILTER_USE_BOTH);
            
            // Ensure selected values are always included
            foreach ($this->filterModelType as $selectedValue) {
                if (isset($allOptions[$selectedValue]) && !isset($options[$selectedValue])) {
                    $options[$selectedValue] = $allOptions[$selectedValue];
                }
            }
        }
        
        return $options;
    }
    
    public function exportCSV()
    {
        $logs = $this->getFilteredLogsQuery()->get();
        
        $filename = 'audit_trail_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header row
            fputcsv($file, [
                'ID',
                'Date & Time',
                'User',
                'User Type',
                'Action',
                'Model Type',
                'Description',
                'IP Address',
            ]);
            
            // Data rows
            foreach ($logs as $log) {
                $userName = trim(implode(' ', array_filter([
                    $log->user_first_name,
                    $log->user_middle_name,
                    $log->user_last_name
                ])));
                
                fputcsv($file, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $userName ?: 'N/A',
                    $this->availableUserTypes[$log->user_type] ?? 'N/A',
                    $this->availableActions[$log->action] ?? ucfirst($log->action),
                    $this->availableModelTypes[$log->model_type] ?? $log->model_type,
                    $log->description ?? 'N/A',
                    $log->ip_address ?? 'N/A',
                ]);
            }
            
            fclose($file);
        };
        
        return Response::stream($callback, 200, $headers);
    }
    
    public function openPrintView()
    {
        $logs = $this->getFilteredLogsQuery()->get();
        $exportData = $logs->map(function($log) {
            $userName = trim(implode(' ', array_filter([
                $log->user_first_name,
                $log->user_middle_name,
                $log->user_last_name
            ])));
            
            return [
                'id' => $log->id,
                'created_at' => $log->created_at->toIso8601String(),
                'user_name' => $userName ?: 'N/A',
                'user_username' => $log->user_username ?? 'N/A',
                'user_type' => $log->user_type,
                'action' => $log->action,
                'model_type' => $log->model_type,
                'model_id' => $log->model_id,
                'description' => $log->description ?? 'N/A',
                'ip_address' => $log->ip_address ?? 'N/A',
            ];
        })->toArray();
        
        $filters = [
            'search' => $this->search,
            'action' => $this->appliedAction,
            'model_type' => $this->appliedModelType,
            'user_type' => $this->appliedUserType,
            'created_from' => $this->appliedCreatedFrom,
            'created_to' => $this->appliedCreatedTo,
        ];
        
        $sorting = $this->sortColumns ?? ['created_at' => 'desc'];
        
        $token = Str::random(32);
        Session::put("export_data_{$token}", $exportData);
        Session::put("export_filters_{$token}", $filters);
        Session::put("export_sorting_{$token}", $sorting);
        Session::put("export_data_{$token}_expires", now()->addMinutes(10));
        
        $printUrl = route('admin.print.audit-trail', ['token' => $token]);
        
        $this->dispatch('open-print-window', ['url' => $printUrl]);
    }
    
    private function getFilteredLogsQuery()
    {
        $query = Log::query();
        
        // Exclude superadmin actions (user_type != 2)
        $query->where('user_type', '!=', 2);
        
        // Search
        if (!empty($this->search)) {
            $searchTerm = trim($this->search);
            $escapedSearchTerm = addcslashes($searchTerm, '%_\\');
            
            // Find model types that match the search term (by readable label)
            $matchingModelTypes = [];
            foreach ($this->availableModelTypes as $modelType => $label) {
                if (stripos($label, $searchTerm) !== false) {
                    $matchingModelTypes[] = $modelType;
                }
            }
            
            $query->where(function ($q) use ($escapedSearchTerm, $matchingModelTypes) {
                // Search username
                $q->where('user_username', 'like', '%' . $escapedSearchTerm . '%')
                  // Search full name (first, middle, last) - similar to guards
                  ->orWhereRaw("CONCAT(COALESCE(user_first_name, ''), ' ', COALESCE(user_middle_name, ''), ' ', COALESCE(user_last_name, '')) LIKE ?", ['%' . $escapedSearchTerm . '%'])
                  // Search individual name fields
                  ->orWhere('user_first_name', 'like', '%' . $escapedSearchTerm . '%')
                  ->orWhere('user_middle_name', 'like', '%' . $escapedSearchTerm . '%')
                  ->orWhere('user_last_name', 'like', '%' . $escapedSearchTerm . '%')
                  // Search model type (raw class name)
                  ->orWhere('model_type', 'like', '%' . $escapedSearchTerm . '%')
                  // Search model type by readable label
                  ->orWhere(function($subQ) use ($matchingModelTypes) {
                      if (!empty($matchingModelTypes)) {
                          $subQ->whereIn('model_type', $matchingModelTypes);
                      }
                  })
                  // Search IP address
                  ->orWhere('ip_address', 'like', '%' . $escapedSearchTerm . '%');
            });
        }
        
        // Filters
        if (!empty($this->appliedAction)) {
            $query->whereIn('action', $this->appliedAction);
        }
        
        if (!empty($this->appliedModelType)) {
            $query->whereIn('model_type', $this->appliedModelType);
        }
        
        if (!is_null($this->appliedUserType)) {
            $query->where('user_type', $this->appliedUserType);
        }
        
        if (!empty($this->appliedCreatedFrom)) {
            $query->whereDate('created_at', '>=', $this->appliedCreatedFrom);
        }
        
        if (!empty($this->appliedCreatedTo)) {
            $query->whereDate('created_at', '<=', $this->appliedCreatedTo);
        }
        
        // Sorting
        foreach ($this->sortColumns as $column => $direction) {
            $query->orderBy($column, $direction);
        }
        
        return $query;
    }
    
    public function render()
    {
        $logs = $this->getFilteredLogsQuery()->paginate(15);
        
        return view('livewire.admin.audit-trail', [
            'logs' => $logs,
            'filterActionOptions' => $this->filterActionOptions,
            'filterModelTypeOptions' => $this->filterModelTypeOptions,
        ]);
    }
}
