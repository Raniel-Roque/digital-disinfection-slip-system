<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\DisinfectionSlip;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;

class SlipArrivalMonitor extends Component
{
    public $currentLocationId;
    public $notificationShown = false;
    public $lastNotificationTime;

    public function mount()
    {
        $this->currentLocationId = Session::get('location_id');

        // Get the last notification time for this user/location from cache
        $cacheKey = 'slip_notification_' . Auth::id() . '_' . $this->currentLocationId;
        $this->lastNotificationTime = Cache::get($cacheKey, now()->subHours(1)); // Default to 1 hour ago if no cache

        $this->checkForArrivals();
    }

    public function checkForArrivals()
    {
        if (!$this->currentLocationId || !Auth::check() || Auth::user()->user_type !== 0) {
            return;
        }

        // Count slips that arrived since the last notification was dismissed
        $newSlipCount = DisinfectionSlip::where('destination_id', $this->currentLocationId)
            ->where('status', 2) // In-Transit
            ->where('updated_at', '>', $this->lastNotificationTime) // Only slips updated after last notification
            ->count();

        if ($newSlipCount > 0) {
            $this->dispatch('slipArrivalPoll', [
                'slipCount' => $newSlipCount,
                'locationId' => $this->currentLocationId,
                'isNew' => !$this->notificationShown
            ]);
            $this->notificationShown = true;
        }
    }

    public function notificationDismissed()
    {
        // Reset the notification state and update the cache
        $this->notificationShown = false;
        $this->lastNotificationTime = now();

        // Store in cache so it persists across page refreshes
        $cacheKey = 'slip_notification_' . Auth::id() . '_' . $this->currentLocationId;
        Cache::put($cacheKey, $this->lastNotificationTime, now()->addHours(24)); // Cache for 24 hours
    }

    public function getListeners()
    {
        return [
            'notificationDismissed' => 'notificationDismissed',
        ];
    }

    public function render()
    {
        return view('livewire.slip-arrival-monitor');
    }
}
