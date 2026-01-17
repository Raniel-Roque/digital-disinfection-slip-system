<?php

namespace App\Livewire\Shared\Issues;

use App\Models\DisinfectionSlip;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\Driver;
use App\Models\User;
use App\Services\Logger;
use Livewire\Component;
use Livewire\Attributes\Renderless;
use Illuminate\Support\Facades\Auth;

class Edit extends Component
{
    public $showModal = false;
    public $showCancelConfirmation = false;
    public $isUpdating = false;
    public $minUserType = 2; // Default for SuperAdmin

    // Slip data
    public $slipId;
    public $selectedSlip = null;

    // Form fields
    public $editVehicleId;
    public $editLocationId;
    public $editDestinationId;
    public $editDriverId;
    public $editHatcheryGuardId;
    public $editReceivedGuardId = null;
    public $editRemarksForDisinfection;
    public $editStatus;

    // Search properties
    public $searchEditVehicle = '';
    public $searchEditOrigin = '';
    public $searchEditDestination = '';
    public $searchEditDriver = '';
    public $searchEditHatcheryGuard = '';
    public $searchEditReceivedGuard = '';

    protected $listeners = ['openEditModal' => 'openModal'];

    public function mount($config = [])
    {
        $this->minUserType = $config['minUserType'] ?? 2;
    }

    public function openModal($slipId)
    {
        if (Auth::user()->user_type < $this->minUserType) {
            abort(403, 'Unauthorized action.');
        }

        $this->slipId = $slipId;
        $this->loadSlipData();
        $this->showModal = true;
    }

    public function loadSlipData()
    {
        $this->selectedSlip = DisinfectionSlip::withTrashed()->with([
            'vehicle' => function($q) { $q->select('id', 'vehicle', 'disabled', 'deleted_at')->withTrashed(); },
            'location' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
            'destination' => function($q) { $q->select('id', 'location_name', 'disabled', 'deleted_at')->withTrashed(); },
            'driver' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'disabled', 'deleted_at')->withTrashed(); },
            'hatcheryGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
            'receivedGuard' => function($q) { $q->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'disabled', 'deleted_at')->withTrashed(); },
        ])->findOrFail($this->slipId);

        $this->editVehicleId = $this->selectedSlip->vehicle_id;
        $this->editLocationId = $this->selectedSlip->location_id;
        $this->editDestinationId = $this->selectedSlip->destination_id;
        $this->editDriverId = $this->selectedSlip->driver_id;
        $this->editHatcheryGuardId = $this->selectedSlip->hatchery_guard_id;
        $this->editReceivedGuardId = $this->selectedSlip->received_guard_id;
        $this->editRemarksForDisinfection = $this->selectedSlip->remarks_for_disinfection;
        $this->editStatus = $this->selectedSlip->status;

        $this->searchEditVehicle = '';
        $this->searchEditOrigin = '';
        $this->searchEditDestination = '';
        $this->searchEditDriver = '';
        $this->searchEditHatcheryGuard = '';
        $this->searchEditReceivedGuard = '';
    }

    public function updatedEditLocationId()
    {
        if ($this->editDestinationId == $this->editLocationId) {
            $this->editDestinationId = null;
        }
    }

    public function updatedEditDestinationId()
    {
        if ($this->editLocationId == $this->editDestinationId) {
            $this->editLocationId = null;
        }
    }

    public function updatedEditHatcheryGuardId()
    {
        if ($this->editReceivedGuardId == $this->editHatcheryGuardId) {
            $this->editReceivedGuardId = null;
        }
    }

    public function updatedEditReceivedGuardId()
    {
        if ($this->editReceivedGuardId == $this->editHatcheryGuardId) {
            $this->editHatcheryGuardId = null;
        }
    }

    public function closeModal()
    {
        if ($this->hasUnsavedChanges()) {
            $this->showCancelConfirmation = true;
        } else {
            $this->resetForm();
            $this->showModal = false;
        }
    }

    public function cancelEdit()
    {
        $this->loadSlipData();
        $this->resetForm();
        $this->showCancelConfirmation = false;
        $this->showModal = false;
    }

    public function resetForm()
    {
        $this->editVehicleId = null;
        $this->editLocationId = null;
        $this->editDestinationId = null;
        $this->editDriverId = null;
        $this->editHatcheryGuardId = null;
        $this->editReceivedGuardId = null;
        $this->editRemarksForDisinfection = null;
        $this->editStatus = null;
        $this->searchEditVehicle = '';
        $this->searchEditOrigin = '';
        $this->searchEditDestination = '';
        $this->searchEditDriver = '';
        $this->searchEditHatcheryGuard = '';
        $this->searchEditReceivedGuard = '';
        $this->resetErrorBag();
    }

    public function hasUnsavedChanges()
    {
        if (!$this->selectedSlip) {
            return false;
        }
        
        return $this->editVehicleId != $this->selectedSlip->vehicle_id ||
               $this->editLocationId != $this->selectedSlip->location_id ||
               $this->editDestinationId != $this->selectedSlip->destination_id ||
               $this->editDriverId != $this->selectedSlip->driver_id ||
               $this->editHatcheryGuardId != $this->selectedSlip->hatchery_guard_id ||
               $this->editReceivedGuardId != $this->selectedSlip->received_guard_id ||
               $this->editRemarksForDisinfection != $this->selectedSlip->remarks_for_disinfection ||
               $this->editStatus != $this->selectedSlip->status;
    }

    public function canEdit()
    {
        if (!$this->selectedSlip) {
            return false;
        }
        if ($this->selectedSlip->vehicle && $this->selectedSlip->vehicle->trashed()) {
            return false;
        }
        return true;
    }

    public function saveEdit()
    {
        if ($this->isUpdating) {
            return;
        }

        if (Auth::user()->user_type < $this->minUserType) {
            abort(403, 'Unauthorized action.');
        }

        $this->isUpdating = true;

        try {
            if (!$this->canEdit()) {
                $this->dispatch('toast', message: 'You are not authorized to edit this slip.', type: 'error');
                return;
            }

            $status = $this->editStatus;
            
            $this->validate([
                'editStatus' => 'required|in:0,1,2',
            ], [], [
                'editStatus' => 'Status',
            ]);
            
            $rules = [
                'editVehicleId' => 'required|exists:vehicles,id',
                'editDestinationId' => [
                    'required',
                    'exists:locations,id',
                    function ($attribute, $value, $fail) {
                        if ($value == $this->editLocationId) {
                            $fail('The destination cannot be the same as the origin.');
                        }
                    },
                ],
                'editDriverId' => 'required|exists:drivers,id',
                'editRemarksForDisinfection' => 'nullable|string|max:1000',
            ];

            if ($status == 0) {
                $rules['editLocationId'] = [
                    'required',
                    'exists:locations,id',
                    function ($attribute, $value, $fail) {
                        if ($value == $this->editDestinationId) {
                            $fail('The origin cannot be the same as the destination.');
                        }
                    },
                ];
                $rules['editHatcheryGuardId'] = [
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
                ];
                $rules['editReceivedGuardId'] = [
                    'nullable',
                    'exists:users,id',
                    function ($attribute, $value, $fail) {
                        if ($value && $value == $this->editHatcheryGuardId) {
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
                ];
            }
            
            // Status 1 or 2: Update origin, hatchery guard, and receiving guard (required)
            if ($status == 1 || $status == 2) {
                $rules['editLocationId'] = [
                    'required',
                    'exists:locations,id',
                    function ($attribute, $value, $fail) {
                        if ($value == $this->editDestinationId) {
                            $fail('The origin cannot be the same as the destination.');
                        }
                    },
                ];
                $rules['editHatcheryGuardId'] = [
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
                ];
                $rules['editReceivedGuardId'] = [
                    'required',
                    'exists:users,id',
                    function ($attribute, $value, $fail) {
                        if ($value && $value == $this->editHatcheryGuardId) {
                            $fail('The receiving guard cannot be the same as the hatchery guard.');
                            return;
                        }
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
                    },
                ];
            }
            
            // Note: Status 3 is not allowed in Issues context (only 0,1,2)
            if ($status == 3) {
                $rules['editLocationId'] = [
                    'required',
                    'exists:locations,id',
                    function ($attribute, $value, $fail) {
                        if ($value == $this->editDestinationId) {
                            $fail('The origin cannot be the same as the destination.');
                        }
                    },
                ];
                $rules['editHatcheryGuardId'] = [
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
                ];
                $rules['editReceivedGuardId'] = [
                    'required',
                    'exists:users,id',
                    function ($attribute, $value, $fail) {
                        if ($value && $value == $this->editHatcheryGuardId) {
                            $fail('The receiving guard cannot be the same as the hatchery guard.');
                            return;
                        }
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
                    },
                ];
            }

            $this->validate($rules, [], [
                'editVehicleId' => 'Vehicle',
                'editLocationId' => 'Origin',
                'editDestinationId' => 'Destination',
                'editDriverId' => 'Driver',
                'editHatcheryGuardId' => 'Hatchery Guard',
                'editReceivedGuardId' => 'Receiving Guard',
                'editRemarksForDisinfection' => 'Remarks for Disinfection',
                'editStatus' => 'Status',
            ]);

            if (!$this->hasUnsavedChanges()) {
                $this->dispatch('toast', message: 'No changes detected.', type: 'info');
                return;
            }

            $sanitizedRemarks = $this->sanitizeText($this->editRemarksForDisinfection);

            $oldValues = $this->selectedSlip->only([
                'vehicle_id', 'location_id', 'destination_id', 'driver_id',
                'hatchery_guard_id', 'received_guard_id', 'remarks_for_disinfection', 'status'
            ]);

            $updateData = [
                'vehicle_id' => $this->editVehicleId,
                'destination_id' => $this->editDestinationId,
                'driver_id' => $this->editDriverId,
                'remarks_for_disinfection' => $sanitizedRemarks,
                'status' => $this->editStatus,
            ];

            if ($status == 0) {
                $updateData['location_id'] = $this->editLocationId;
                $updateData['hatchery_guard_id'] = $this->editHatcheryGuardId;
                $updateData['received_guard_id'] = $this->editReceivedGuardId;
            }
            
            if ($status == 1 || $status == 2) {
                $updateData['location_id'] = $this->editLocationId;
                $updateData['hatchery_guard_id'] = $this->editHatcheryGuardId;
                $updateData['received_guard_id'] = $this->editReceivedGuardId;
            }

            $this->selectedSlip->update($updateData);

            $this->selectedSlip->refresh();
            $this->selectedSlip->load([
                'vehicle' => function($q) { $q->withTrashed(); },
                'location' => function($q) { $q->withTrashed(); },
                'destination' => function($q) { $q->withTrashed(); },
                'driver' => function($q) { $q->withTrashed(); },
                'hatcheryGuard' => function($q) { $q->withTrashed(); },
                'receivedGuard' => function($q) { $q->withTrashed(); },
            ]);

            $slipId = $this->selectedSlip->slip_id;
            
            Logger::update(
                DisinfectionSlip::class,
                $this->selectedSlip->id,
                "Updated slip {$slipId}",
                $oldValues,
                $updateData
            );
            
            $this->resetForm();
            $this->showModal = false;
            $this->dispatch('toast', message: "{$slipId} has been updated.", type: 'success');
            $this->dispatch('slip-updated');
        } finally {
            $this->isUpdating = false;
        }
    }

    private function sanitizeText($text)
    {
        if (empty($text)) {
            return null;
        }
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/u', '', $text);
        $text = preg_replace('/\r\n|\r/', "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = trim($text);
        return empty($text) ? null : $text;
    }

    #[Renderless]
    public function getPaginatedVehicles($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = Vehicle::query()
            ->whereNull('deleted_at')
            ->where('disabled', false)
            ->select(['id', 'vehicle']);

        if (!empty($search)) {
            $query->where('vehicle', 'like', '%' . $search . '%');
        }

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
        $offset = ($page - 1) * $perPage;
        $total = $query->count();
        $results = $query->skip($offset)->take($perPage)->get();
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

        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('first_name', 'like', $searchTerm)
                  ->orWhere('middle_name', 'like', $searchTerm)
                  ->orWhere('last_name', 'like', $searchTerm);
            });
        }

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
        $offset = ($page - 1) * $perPage;
        $total = $query->count();
        $results = $query->skip($offset)->take($perPage)->get();
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

        if (!empty($search)) {
            $query->where('location_name', 'like', '%' . $search . '%');
        }

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
        $offset = ($page - 1) * $perPage;
        $total = $query->count();
        $results = $query->skip($offset)->take($perPage)->get();
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

        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('first_name', 'like', $searchTerm)
                  ->orWhere('middle_name', 'like', $searchTerm)
                  ->orWhere('last_name', 'like', $searchTerm)
                  ->orWhere('username', 'like', $searchTerm);
            });
        }

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
        $offset = ($page - 1) * $perPage;
        $total = $query->count();
        $results = $query->skip($offset)->take($perPage)->get();
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

    public function render()
    {
        return view('livewire.shared.issues.edit');
    }
}
