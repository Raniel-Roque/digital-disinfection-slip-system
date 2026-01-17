<?php

namespace App\Livewire\SuperAdmin;

use App\Models\DisinfectionSlip;
use App\Models\User;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\Issue;
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
            'total_vehicles' => $this->getVehiclesCount(),
            'total_locations' => $this->getLocationsCount(),
            'unresolved_issues' => $this->getUnresolvedIssuesCount(),
            'total_created_slips_today' => $this->getTotalCreatedSlipsTodayCount(),
            'in_progress_slips_today' => $this->getInProgressSlipsTodayCount(),
        ];
    }

    /**
     * Get count of disinfected slips this week
     */
    private function getWeekDisinfectedCount()
    {
        return DisinfectionSlip::where('status', 3)
            ->whereNull('deleted_at')
            ->whereBetween('completed_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])
            ->count();
    }

    /**
     * Get count of disinfected vehicles this month
     */
    private function getMonthDisinfectedCount()
    {
        return DisinfectionSlip::where('status', 3)
            ->whereNull('deleted_at')
            ->whereMonth('completed_at', Carbon::now()->month)
            ->whereYear('completed_at', Carbon::now()->year)
            ->count();
    }

    /**
     * Get count of disinfected vehicles this year
     */
    private function getYearDisinfectedCount()
    {
        return DisinfectionSlip::where('status', 3)
            ->whereNull('deleted_at')
            ->whereYear('completed_at', Carbon::now()->year)
            ->count();
    }

    /**
     * Get total count of all disinfected vehicles (all-time)
     */
    private function getTotalDisinfectedCount()
    {
        return DisinfectionSlip::where('status', 3)
            ->whereNull('deleted_at')
            ->whereNotNull('completed_at')
            ->count();
    }

    /**
     * Get count of active guards (user_type = 0, not soft deleted)
     */
    private function getGuardsCount()
    {
        return User::where('user_type', 0)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Get count of active admins (user_type = 1, not soft deleted)
     */
    private function getAdminsCount()
    {
        return User::where('user_type', 1)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Get count of active drivers (not disabled, not soft deleted)
     */
    private function getDriversCount()
    {
        return Driver::where('disabled', false)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Get count of unique vehicles (not disabled, not soft deleted)
     */
    private function getVehiclesCount()
    {
        return Vehicle::where('disabled', false)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Get count of active locations (not disabled, not soft deleted)
     */
    private function getLocationsCount()
    {
        return Location::where('disabled', false)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Get count of unresolved issues
     */
    private function getUnresolvedIssuesCount()
    {
        return Issue::whereNull('resolved_at')
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Get count of total created slips today (all statuses)
     */
    private function getTotalCreatedSlipsTodayCount()
    {
        return DisinfectionSlip::whereDate('created_at', Carbon::today())
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Get count of in progress slips today (status 0, 1, 2 - Pending, Disinfecting, In-Transit)
     */
    private function getInProgressSlipsTodayCount()
    {
        return DisinfectionSlip::whereDate('created_at', Carbon::today())
            ->whereIn('status', [0, 1, 2])
            ->whereNull('deleted_at')
            ->count();
    }

    public function render()
    {
        return view('livewire.super-admin.super-admin-dashboard');
    }
}

