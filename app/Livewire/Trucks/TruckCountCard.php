<?php

namespace App\Livewire\Trucks;

use Livewire\Component;
use App\Models\DisinfectionSlip;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class TruckCountCard extends Component
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
                // Incoming trucks today
                $query->whereDate('created_at', today())
                      ->where('destination_id', $locationId)
                      ->where(function($q) {
                          // Status 0 (Ongoing) without a receiving guard
                          $q->where(function($q2) {
                              $q2->where('status', 0)
                                 ->whereNull('received_guard_id');
                          })
                          // OR Status 1 (Disinfecting) received by current user
                          ->orWhere(function($q2) {
                              $q2->where('status', 1)
                                 ->where('received_guard_id', Auth::id());
                          });
                      });
                break;

            case 'outgoing':
                // Outgoing trucks today - only show slips created by the current user
                $query->whereDate('created_at', today())
                      ->where('location_id', $locationId)
                      ->where('hatchery_guard_id', Auth::id())
                      ->whereIn('status', [0, 1]);
                break;

            case 'total':
                // Total slips today (all statuses)
                $query->whereDate('created_at', today());
                break;

            case 'inprogress':
                // Currently in progress (status 1) - only for auth guard
                $query->where('status', 1)
                      ->where('received_guard_id', Auth::id());
                break;

            case 'completed':
                // Completed today - Only show slips received/completed by the current user
                $query->where('status', 2)
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
        return view('livewire.trucks.truck-count-card');
    }
}