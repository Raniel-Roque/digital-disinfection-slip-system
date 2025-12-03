<?php

namespace App\Livewire\Sidebar;

use Livewire\Component;

class SidebarUser extends Component
{
    public $currentRoute;

    public function mount($currentRoute)
    {
        $this->currentRoute = $currentRoute;
    }

    public function render()
    {
        return view('livewire.sidebar.sidebar-user');
    }
}
