<?php

namespace App\Livewire\Vehicles;

use Livewire\Component;
use App\Models\DisinfectionSlip;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Cache;
class SlipCountCard extends Component
{
    public $type; // 'incoming', 'outgoing', 'total', 'inprogress', 'completed'

    public function mount($type)
    {
        $this->type = $type;
    }

    #[Computed]
    public function count()
    {
        $locationId = Session::get('location_id');

        if (!$locationId) {
            return 0;
        }

        // Base query for all types
        $query = DisinfectionSlip::where(function($q) use ($locationId) {
            $q->where('location_id', $locationId)
              ->orWhere('destination_id', $locationId);
        });

        // Apply filters based on type
        switch ($this->type) {
            case 'incoming':
                // Incoming slips today - Status 2 (In-Transit) only - unclaimed or claimed by current user
                $query->whereDate('created_at', today())
                      ->where('destination_id', $locationId)
                      ->where('location_id', '!=', $locationId)
                      ->where('status', 2)
                      ->where(function($q) {
                          $q->whereNull('received_guard_id')
                            ->orWhere('received_guard_id', Auth::id());
                      });
                break;

            case 'outgoing':
                // Outgoing slips today - Status 0, 1, 2 (Pending, Disinfecting, In-Transit) - only show slips created by the current user
                $query->whereDate('created_at', today())
                      ->where('location_id', $locationId)
                      ->where('hatchery_guard_id', Auth::id())
                      ->whereIn('status', [0, 1, 2]);
                break;

            case 'total':
                // Total slips today (all statuses) - only for current user
                $query->whereDate('created_at', today())
                      ->where(function($q) use ($locationId) {
                          // Slips created by current user (outgoing)
                          $q->where(function($q2) use ($locationId) {
                              $q2->where('location_id', $locationId)
                                 ->where('hatchery_guard_id', Auth::id());
                          })
                          // OR slips received by current user (incoming)
                          ->orWhere(function($q2) use ($locationId) {
                              $q2->where('destination_id', $locationId)
                                 ->where('received_guard_id', Auth::id());
                          });
                      });
                break;

            case 'inprogress':
                // Currently processing - includes:
                // Outgoing: status 1 (disinfecting) created by current user
                // Incoming: status 2 (in-transit) claimed by current user
                $query->where(function($q) use ($locationId) {
                    // Outgoing slips being disinfected
                    $q->where('status', 1)
                      ->where('location_id', $locationId)
                      ->where('hatchery_guard_id', Auth::id())
                      // OR Incoming slips claimed by current user
                      ->orWhere(function($q2) use ($locationId) {
                          $q2->where('status', 2)
                             ->where('destination_id', $locationId)
                             ->where('received_guard_id', Auth::id());
                      });
                });
                break;

            case 'pending':
                // Pending outgoing slips - Status 0 - created by current user
                $query->whereDate('created_at', today())
                      ->where('location_id', $locationId)
                      ->where('hatchery_guard_id', Auth::id())
                      ->where('status', 0);
                break;

            case 'completed':
                // Completed today - Status 3 - Only show slips received/completed by the current user
                $query->where('status', 3)
                      ->whereDate('completed_at', today())
                      ->where(function($q) use ($locationId) {
                          // Outgoing: show if created by current user
                          $q->where(function($q2) use ($locationId) {
                              $q2->where('location_id', $locationId)
                                 ->where('hatchery_guard_id', Auth::id());
                          })
                          // Incoming: show if received/completed by current user
                          ->orWhere(function($q2) use ($locationId) {
                              $q2->where('destination_id', $locationId)
                                 ->where('received_guard_id', Auth::id());
                          });
                      });
                break;
        }

        return $query->count();
    }

    public function render()
    {
        return view('livewire.slips.slip-count-card');
    }
}