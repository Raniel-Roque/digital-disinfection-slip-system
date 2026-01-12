<?php

namespace App\Livewire\SuperAdmin;

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
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    
    // Filter properties
    public $filterAction = [];
    public $filterModelType = [];
    public $filterUserType = [];
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
        'App\\Models\\Attachment' => 'Attachment',
        'App\\Models\\User' => 'User',
        'App\\Models\\Driver' => 'Driver',
        'App\\Models\\Location' => 'Location',
        'App\\Models\\Truck' => 'Truck',
        'App\\Models\\Setting' => 'Setting',
        'App\\Models\\Report' => 'Report',
        'App\\Models\\Reason' => 'Reason',
    ];
    
    public $availableUserTypes = [
        0 => 'Guard',
        1 => 'Admin',
        2 => 'Super Admin',
    ];
    
    protected $queryString = ['search'];
    
    public function mount()
    {
        // Initialize array filters
        $this->filterAction = [];
        $this->filterModelType = [];
        $this->filterUserType = [];
        $this->appliedAction = [];
        $this->appliedModelType = [];
        $this->appliedUserType = null;
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
    
    public function getSortDirection($column)
    {
        return $this->sortBy === $column ? $this->sortDirection : null;
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
            case 'user_type':
                $this->appliedUserType = array_values(array_filter($this->appliedUserType, fn($v) => $v != $value)); // Use != to handle string/int conversion
                $this->filterUserType = array_values(array_filter($this->filterUserType, fn($v) => $v != $value));
                // If array becomes empty, set to null
                if (empty($this->appliedUserType)) {
                    $this->appliedUserType = null;
                    $this->filterUserType = null;
                }
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
        $this->filterUserType = [];
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
    
    // Helper method to ensure selected values are always included in filtered options
    private function ensureSelectedInOptions($options, $selectedValues, $allOptions)
    {
        if (empty($selectedValues)) {
            return $options;
        }
        
        $optionsArray = is_array($options) ? $options : $options->toArray();
        
        // Add selected values if they're not already in the filtered options
        foreach ($selectedValues as $selectedValue) {
            if (isset($allOptions[$selectedValue]) && !isset($optionsArray[$selectedValue])) {
                $optionsArray[$selectedValue] = $allOptions[$selectedValue];
            }
        }
        
        return $optionsArray;
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
                'user_type' => $this->availableUserTypes[$log->user_type] ?? 'N/A', // Map user type
                'action' => $this->availableActions[$log->action] ?? ucfirst($log->action), // Map action
                'model_type' => $this->availableModelTypes[$log->model_type] ?? $log->model_type, // Map model type
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
        
        $sorting = [$this->sortBy => $this->sortDirection];
        
        $token = Str::random(32);
        Session::put("export_data_{$token}", $exportData);
        Session::put("export_filters_{$token}", $filters);
        Session::put("export_sorting_{$token}", $sorting);
        Session::put("export_data_{$token}_expires", now()->addMinutes(10));
        
        $printUrl = route('superadmin.print.audit-trail', ['token' => $token]);
        
        $this->dispatch('open-print-window', ['url' => $printUrl]);
    }
    private function getFilteredLogsQuery()
    {
        $query = Log::query();
        
        // Search
        if (!empty($this->search)) {
            $searchTerm = trim($this->search);
            $searchTermLower = strtolower($searchTerm);
            $searchTermNoAt = ltrim($searchTerm, '@'); // Remove @ from beginning for username searches
            $searchTermNoAtLower = strtolower($searchTermNoAt);
            
            // Find model types that match the search term (by readable label)
            $matchingModelTypes = [];
            foreach ($this->availableModelTypes as $modelType => $label) {
                if (stripos(strtolower($label), $searchTermLower) !== false) {
                    $matchingModelTypes[] = $modelType;
                }
            }
            
            $query->where(function ($q) use ($searchTerm, $searchTermLower, $searchTermNoAt, $searchTermNoAtLower, $matchingModelTypes) {
                // Search by ID (exact match or starts with)
                $q->where('id', $searchTerm)
                  ->orWhere('id', 'like', $searchTerm . '%')
                  // Search username (exact, starts with, or contains)
                  ->orWhere('user_username', 'like', '%' . $searchTerm . '%')
                  ->orWhere('user_username', 'like', $searchTerm . '%')
                  ->orWhereRaw('LOWER(user_username) = ?', [$searchTermLower])
                  // Also search username without @ symbol
                  ->orWhere('user_username', 'like', '%' . $searchTermNoAt . '%')
                  ->orWhere('user_username', 'like', $searchTermNoAt . '%')
                  ->orWhereRaw('LOWER(user_username) = ?', [$searchTermNoAtLower])
                  // Search full name (concatenated with proper NULL handling)
                  ->orWhereRaw("LOWER(CONCAT(COALESCE(user_first_name, ''), ' ', COALESCE(user_middle_name, ''), ' ', COALESCE(user_last_name, ''))) LIKE ?", ['%' . $searchTermLower . '%'])
                  // Search individual name fields
                  ->orWhereRaw('LOWER(user_first_name) LIKE ?', ['%' . $searchTermLower . '%'])
                  ->orWhereRaw('LOWER(user_middle_name) LIKE ?', ['%' . $searchTermLower . '%'])
                  ->orWhereRaw('LOWER(user_last_name) LIKE ?', ['%' . $searchTermLower . '%'])
                  // Search model type (raw class name)
                  ->orWhere('model_type', 'like', '%' . $searchTerm . '%')
                  ->orWhereRaw('LOWER(model_type) LIKE ?', ['%' . $searchTermLower . '%'])
                  // Search model type by readable label
                  ->orWhere(function($subQ) use ($matchingModelTypes) {
                      if (!empty($matchingModelTypes)) {
                          $subQ->whereIn('model_type', $matchingModelTypes);
                      }
                  })
                  // Search IP address
                  ->orWhere('ip_address', 'like', '%' . $searchTerm . '%')
                  ->orWhere('ip_address', 'like', $searchTerm . '%')
                  // Search description
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhereRaw('LOWER(description) LIKE ?', ['%' . $searchTermLower . '%'])
                  // Search action
                  ->orWhere('action', 'like', '%' . $searchTerm . '%')
                  ->orWhereRaw('LOWER(action) = ?', [$searchTermLower]);
            });
        }
        
        // Filters
        if (!empty($this->appliedAction)) {
            $query->whereIn('action', $this->appliedAction);
        }
        
        if (!empty($this->appliedModelType)) {
            $query->whereIn('model_type', $this->appliedModelType);
        }
        
        if (!empty($this->appliedUserType)) {
            $query->whereIn('user_type', $this->appliedUserType);
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
        $logs = $this->getFilteredLogsQuery()->paginate(15);
        
        return view('livewire.super-admin.audit-trail', [
            'logs' => $logs,
            'filterActionOptions' => $this->filterActionOptions,
            'filterModelTypeOptions' => $this->filterModelTypeOptions,
        ]);
    }
}
