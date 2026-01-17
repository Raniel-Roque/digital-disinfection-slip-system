<?php

namespace App\Livewire\Shared\Slips;

use App\Models\DisinfectionSlip;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\Driver;
use App\Models\User;
use App\Models\Reason;
use App\Services\Logger;
use Livewire\Component;
use Livewire\Attributes\Renderless;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Create extends Component
{
    public $showModal = false;
    public $showCancelConfirmation = false;
    public $isCreating = false;
    public $minUserType = 2; // Default for SuperAdmin

    // Form fields
    public $vehicle_id;
    public $location_id; // Origin
    public $destination_id;
    public $driver_id;
    public $hatchery_guard_id;
    public $received_guard_id = null; // Optional receiving guard
    public $reason_id;
    public $remarks_for_disinfection;

    // Search properties for dropdowns
    public $searchOrigin = '';
    public $searchDestination = '';
    public $searchVehicle = '';
    public $searchDriver = '';
    public $searchHatcheryGuard = '';
    public $searchReceivedGuard = '';
    public $searchReason = '';

    protected $listeners = ['openCreateModal' => 'openModal'];

    public function mount($config = [])
    {
        $this->minUserType = $config['minUserType'] ?? 2;
    }

    public function openModal()
    {
        // Authorization check
        if (Auth::user()->user_type < $this->minUserType) {
            abort(403, 'Unauthorized action.');
        }

        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        // Check if form has unsaved changes
        if ($this->hasUnsavedChanges()) {
            $this->showCancelConfirmation = true;
        } else {
            $this->resetForm();
            $this->showModal = false;
        }
    }

    public function cancelCreate()
    {
        $this->resetForm();
        $this->showCancelConfirmation = false;
        $this->showModal = false;
    }

    public function resetForm()
    {
        $this->vehicle_id = null;
        $this->location_id = null;
        $this->destination_id = null;
        $this->driver_id = null;
        $this->hatchery_guard_id = null;
        $this->received_guard_id = null;
        $this->reason_id = null;
        $this->remarks_for_disinfection = null;
        $this->searchOrigin = '';
        $this->searchDestination = '';
        $this->searchVehicle = '';
        $this->searchDriver = '';
        $this->searchHatcheryGuard = '';
        $this->searchReceivedGuard = '';
        $this->searchReason = '';
        $this->resetErrorBag();
    }

    public function hasUnsavedChanges()
    {
        return !empty($this->vehicle_id) || 
               !empty($this->location_id) || 
               !empty($this->destination_id) || 
               !empty($this->driver_id) || 
               !empty($this->hatchery_guard_id) || 
               !empty($this->received_guard_id) || 
               !empty($this->reason_id) ||
               !empty($this->remarks_for_disinfection);
    }

    // Watch for changes to location_id or destination_id to prevent same selection
    public function updatedLocationId()
    {
        // If destination is the same as origin, clear it
        if ($this->destination_id == $this->location_id) {
            $this->destination_id = null;
        }
    }

    public function updatedDestinationId()
    {
        // If origin is the same as destination, clear it
        if ($this->location_id == $this->destination_id) {
            $this->location_id = null;
        }
    }

    public function updatedHatcheryGuardId()
    {
        // If receiving guard is the same as hatchery guard, clear it
        if ($this->received_guard_id == $this->hatchery_guard_id) {
            $this->received_guard_id = null;
        }
    }

    public function updatedReceivedGuardId()
    {
        // If receiving guard is set to hatchery guard, clear the hatchery guard
        if ($this->received_guard_id == $this->hatchery_guard_id) {
            $this->hatchery_guard_id = null;
        }
    }

    public function createSlip()
    {
        // Prevent multiple submissions
        if ($this->isCreating) {
            return;
        }

        // Authorization check
        if (Auth::user()->user_type < $this->minUserType) {
            abort(403, 'Unauthorized action.');
        }

        $this->isCreating = true;

        try {
            $this->validate([
                'vehicle_id' => 'required|exists:vehicles,id',
                'location_id' => [
                    'required',
                    'exists:locations,id',
                    function ($attribute, $value, $fail) {
                        if ($value == $this->destination_id) {
                            $fail('The origin cannot be the same as the destination.');
                        }
                    },
                ],
                'destination_id' => [
                    'required',
                    'exists:locations,id',
                    function ($attribute, $value, $fail) {
                        if ($value == $this->location_id) {
                            $fail('The destination cannot be the same as the origin.');
                        }
                    },
                ],
                'driver_id' => 'required|exists:drivers,id',
                'hatchery_guard_id' => [
                    'required',
                    'exists:users,id',
                    function ($attribute, $value, $fail) {
                        $guard = User::find($value);
                        if (!$guard) {
                            $fail('The selected hatchery guard does not exist.');
                            return;
                        }
                        if ($guard->user_type !== 0) {
                            $fail('The selected user is not a guard.');
                            return;
                        }
                        if ($guard->disabled) {
                            $fail('The selected hatchery guard has been disabled.');
                        }
                    },
                ],
                'received_guard_id' => [
                    'nullable',
                    'exists:users,id',
                    function ($attribute, $value, $fail) {
                        if ($value && $value == $this->hatchery_guard_id) {
                            $fail('The receiving guard cannot be the same as the hatchery guard.');
                            return;
                        }
                        if ($value) {
                            $guard = User::find($value);
                            if (!$guard) {
                                $fail('The selected receiving guard does not exist.');
                                return;
                            }
                            if ($guard->user_type !== 0) {
                                $fail('The selected user is not a guard.');
                                return;
                            }
                            if ($guard->disabled) {
                                $fail('The selected receiving guard has been disabled.');
                            }
                        }
                    },
                ],
                'reason_id' => [
                    'required',
                    'exists:reasons,id',
                    function ($attribute, $value, $fail) {
                        if ($value) {
                            $reason = Reason::find($value);
                            if (!$reason || $reason->is_disabled) {
                                $fail('The selected reason is not available.');
                            }
                        }
                    },
                ],
                'remarks_for_disinfection' => 'nullable|string|max:1000',
            ], [], [
                'location_id' => 'Origin',
                'destination_id' => 'Destination',
                'vehicle_id' => 'Vehicle',
                'driver_id' => 'Driver',
                'hatchery_guard_id' => 'Hatchery Guard',
                'received_guard_id' => 'Receiving Guard',
                'reason_id' => 'Reason',
                'remarks_for_disinfection' => 'Remarks for Disinfection',
            ]);

            // Sanitize remarks_for_disinfection
            $sanitizedRemarks = $this->sanitizeText($this->remarks_for_disinfection);

            $slip = DisinfectionSlip::create([
                'vehicle_id' => $this->vehicle_id,
                'location_id' => $this->location_id,
                'destination_id' => $this->destination_id,
                'driver_id' => $this->driver_id,
                'hatchery_guard_id' => $this->hatchery_guard_id,
                'received_guard_id' => $this->received_guard_id,
                'reason_id' => $this->reason_id,
                'remarks_for_disinfection' => $sanitizedRemarks,
                'status' => 0, // Pending
            ]);

            $slipId = $slip->slip_id;
            
            // Log the create action
            Logger::create(
                DisinfectionSlip::class,
                $slip->id,
                "Created slip {$slipId}",
                $slip->only([
                    'vehicle_id',
                    'location_id',
                    'destination_id',
                    'driver_id',
                    'hatchery_guard_id',
                    'received_guard_id',
                    'reason_id',
                    'remarks_for_disinfection',
                    'status'
                ])
            );
            
            $this->dispatch('toast', message: "{$slipId} has been created.", type: 'success');
            $this->dispatch('slip-created');
            
            // Close modal and reset form
            $this->resetForm();
            $this->showModal = false;
        } finally {
            $this->isCreating = false;
        }
    }

    /**
     * Sanitize text input
     */
    private function sanitizeText($text)
    {
        if (empty($text)) {
            return null;
        }

        // Remove HTML tags
        $text = strip_tags($text);
        
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove control characters (but preserve newlines \n and carriage returns \r)
        $text = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/u', '', $text);
        
        // Normalize line endings to \n
        $text = preg_replace('/\r\n|\r/', "\n", $text);
        
        // Normalize multiple spaces to single space (but preserve newlines)
        $text = preg_replace('/[ \t]+/', ' ', $text);
        
        // Normalize multiple newlines to double newline max
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        // Trim whitespace from start and end
        $text = trim($text);
        
        return empty($text) ? null : $text;
    }

    // Paginated data fetching methods for searchable dropdowns
    #[Renderless]
    public function getPaginatedVehicles($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = Vehicle::query()
            ->whereNull('deleted_at')
            ->where('disabled', false)
            ->select(['id', 'vehicle']);

        // Apply search filter
        if (!empty($search)) {
            $query->where('vehicle', 'like', '%' . $search . '%');
        }

        // Include specific IDs (for selected items)
        if (!empty($includeIds)) {
            $includedItems = Vehicle::whereIn('id', $includeIds)
                ->select(['id', 'vehicle'])
                ->orderBy('vehicle', 'asc')
                ->get()
                ->pluck('vehicle', 'id')
                ->toArray();
            return [
                'data' => $includedItems,
                'has_more' => false,
                'total' => count($includedItems),
            ];
        }

        $query->orderBy('vehicle', 'asc');
        
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Get total count for this query
        $total = $query->count();
        
        // Get paginated results
        $results = $query->skip($offset)->take($perPage)->get();
        
        // Convert to array format
        $data = $results->pluck('vehicle', 'id')->toArray();
        
        return [
            'data' => $data,
            'has_more' => ($offset + $perPage) < $total,
            'total' => $total,
        ];
    }

    #[Renderless]
    public function getPaginatedDrivers($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = Driver::query()
            ->whereNull('deleted_at')
            ->where('disabled', false)
            ->select(['id', 'first_name', 'middle_name', 'last_name']);

        // Apply search filter
        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('first_name', 'like', $searchTerm)
                  ->orWhere('middle_name', 'like', $searchTerm)
                  ->orWhere('last_name', 'like', $searchTerm);
            });
        }

        // Include specific IDs (for selected items)
        if (!empty($includeIds)) {
            $includedItems = Driver::whereIn('id', $includeIds)
                ->select(['id', 'first_name', 'middle_name', 'last_name'])
                ->orderBy('first_name', 'asc')
                ->get()
                ->mapWithKeys(function ($driver) {
                    return [$driver->id => trim("{$driver->first_name} {$driver->middle_name} {$driver->last_name}")];
                })
                ->toArray();
            return [
                'data' => $includedItems,
                'has_more' => false,
                'total' => count($includedItems),
            ];
        }

        $query->orderBy('first_name', 'asc');
        
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Get total count for this query
        $total = $query->count();
        
        // Get paginated results
        $results = $query->skip($offset)->take($perPage)->get();
        
        // Convert to array format
        $data = $results->mapWithKeys(function ($driver) {
            return [$driver->id => trim("{$driver->first_name} {$driver->middle_name} {$driver->last_name}")];
        })->toArray();
        
        return [
            'data' => $data,
            'has_more' => ($offset + $perPage) < $total,
            'total' => $total,
        ];
    }

    #[Renderless]
    public function getPaginatedLocations($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = Location::query()
            ->whereNull('deleted_at')
            ->where('disabled', false)
            ->select(['id', 'location_name']);

        // Apply search filter
        if (!empty($search)) {
            $query->where('location_name', 'like', '%' . $search . '%');
        }

        // Include specific IDs (for selected items)
        if (!empty($includeIds)) {
            $includedItems = Location::whereIn('id', $includeIds)
                ->select(['id', 'location_name'])
                ->orderBy('location_name', 'asc')
                ->get()
                ->pluck('location_name', 'id')
                ->toArray();
            return [
                'data' => $includedItems,
                'has_more' => false,
                'total' => count($includedItems),
            ];
        }

        $query->orderBy('location_name', 'asc');
        
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Get total count for this query
        $total = $query->count();
        
        // Get paginated results
        $results = $query->skip($offset)->take($perPage)->get();
        
        // Convert to array format
        $data = $results->pluck('location_name', 'id')->toArray();
        
        return [
            'data' => $data,
            'has_more' => ($offset + $perPage) < $total,
            'total' => $total,
        ];
    }

    #[Renderless]
    public function getPaginatedGuards($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = User::query()
            ->where('user_type', 0)
            ->where('disabled', false)
            ->select(['id', 'first_name', 'middle_name', 'last_name', 'username']);

        // Apply search filter
        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('first_name', 'like', $searchTerm)
                  ->orWhere('middle_name', 'like', $searchTerm)
                  ->orWhere('last_name', 'like', $searchTerm)
                  ->orWhere('username', 'like', $searchTerm);
            });
        }

        // Include specific IDs (for selected items)
        if (!empty($includeIds)) {
            $includedItems = User::whereIn('id', $includeIds)
                ->where('user_type', 0)
                ->select(['id', 'first_name', 'middle_name', 'last_name', 'username'])
                ->orderBy('first_name', 'asc')->orderBy('last_name', 'asc')
                ->get()
                ->mapWithKeys(function ($user) {
                    $name = trim("{$user->first_name} {$user->middle_name} {$user->last_name}");
                    return [$user->id => "{$name} @{$user->username}"];
                })
                ->toArray();
            return [
                'data' => $includedItems,
                'has_more' => false,
                'total' => count($includedItems),
            ];
        }

        $query->orderBy('first_name', 'asc')->orderBy('last_name', 'asc');
        
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Get total count for this query
        $total = $query->count();
        
        // Get paginated results
        $results = $query->skip($offset)->take($perPage)->get();
        
        // Convert to array format
        $data = $results->mapWithKeys(function ($user) {
            $name = trim("{$user->first_name} {$user->middle_name} {$user->last_name}");
            return [$user->id => "{$name} @{$user->username}"];
        })->toArray();
        
        return [
            'data' => $data,
            'has_more' => ($offset + $perPage) < $total,
            'total' => $total,
        ];
    }

    #[Renderless]
    public function getPaginatedReasons($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = Reason::query()
            ->where('is_disabled', false)
            ->select(['id', 'reason_text']);

        // Apply search filter
        if (!empty($search)) {
            $query->where('reason_text', 'like', '%' . $search . '%');
        }

        // Include specific IDs (for selected items)
        if (!empty($includeIds)) {
            $includedItems = Reason::whereIn('id', $includeIds)
                ->select(['id', 'reason_text'])
                ->orderBy('reason_text', 'asc')
                ->get()
                ->pluck('reason_text', 'id')
                ->toArray();
            return [
                'data' => $includedItems,
                'has_more' => false,
                'total' => count($includedItems),
            ];
        }

        $query->orderBy('reason_text', 'asc');
        
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Get total count for this query
        $total = $query->count();
        
        // Get paginated results
        $results = $query->skip($offset)->take($perPage)->get();
        
        // Convert to array format
        $data = $results->pluck('reason_text', 'id')->toArray();
        
        return [
            'data' => $data,
            'has_more' => ($offset + $perPage) < $total,
            'total' => $total,
        ];
    }

    public function render()
    {
        return view('livewire.shared.slips.create');
    }
}
