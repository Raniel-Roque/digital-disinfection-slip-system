<?php

namespace App\Livewire\SuperAdmin;

use App\Models\DisinfectionSlip;
use App\Models\User;
use App\Models\Driver;
use App\Models\Truck;
use App\Models\Location;
use Carbon\Carbon;
use Livewire\Component;

class SuperAdminDashboard extends Component
{
    public function getStatsProperty()
    {
        return [
            'week_disinfected' => $this->getWeekDisinfectedCount(),
            'month_disinfected' => $this->getMonthDisinfectedCount(),
            'year_disinfected' => $this->getYearDisinfectedCount(),
            'total_disinfected' => $this->getTotalDisinfectedCount(),
            'total_guards' => $this->getGuardsCount(),
            'total_admins' => $this->getAdminsCount(),
            'total_drivers' => $this->getDriversCount(),
            'total_plate_numbers' => $this->getPlateNumbersCount(),
            'total_locations' => $this->getLocationsCount(),
        ];
    }

    /**
     * Get count of disinfected trucks this week
     */
    private function getWeekDisinfectedCount()
    {
        return DisinfectionSlip::where('status', 2)
            ->whereBetween('completed_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])
            ->count();
    }

    /**
     * Get count of disinfected trucks this month
     */
    private function getMonthDisinfectedCount()
    {
        return DisinfectionSlip::where('status', 2)
            ->whereMonth('completed_at', Carbon::now()->month)
            ->whereYear('completed_at', Carbon::now()->year)
            ->count();
    }

    /**
     * Get count of disinfected trucks this year
     */
    private function getYearDisinfectedCount()
    {
        return DisinfectionSlip::where('status', 2)
            ->whereYear('completed_at', Carbon::now()->year)
            ->count();
    }

    /**
     * Get total count of all disinfected trucks (all-time)
     */
    private function getTotalDisinfectedCount()
    {
        return DisinfectionSlip::where('status', 2)
            ->whereNotNull('completed_at')
            ->count();
    }

    /**
     * Get count of active guards (user_type = 0, not soft deleted)
     */
    private function getGuardsCount()
    {
        return User::where('user_type', 0)
            ->count();
    }

    /**
     * Get count of active admins (user_type = 1, not soft deleted)
     */
    private function getAdminsCount()
    {
        return User::where('user_type', 1)
            ->count();
    }

    /**
     * Get count of active drivers (not disabled, not soft deleted)
     */
    private function getDriversCount()
    {
        return Driver::where('disabled', false)
            ->count();
    }

    /**
     * Get count of unique plate numbers (not disabled, not soft deleted)
     */
    private function getPlateNumbersCount()
    {
        return Truck::where('disabled', false)
            ->count();
    }

    /**
     * Get count of active locations (not disabled, not soft deleted)
     */
    private function getLocationsCount()
    {
        return Location::where('disabled', false)
            ->count();
    }

    public function render()
    {
        return view('livewire.superadmin.superadmin-dashboard');
    }
}

