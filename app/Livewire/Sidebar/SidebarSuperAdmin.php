<?php

namespace App\Livewire\Sidebar;

use Livewire\Component;
use Illuminate\Support\Facades\Route;

class SidebarSuperAdmin extends Component
{
    public $currentRoute;

    public function mount()
    {
        $this->currentRoute = Route::currentRouteName();
    }

    public function render()
    {
        return view('livewire.sidebar.sidebar-superadmin');
    }
}
