<?php

namespace App\Livewire\Admin;

use App\Livewire\Shared\Vehicles as SharedVehicles;

class Vehicles extends SharedVehicles
{
    public function mount($config = [])
    {
        parent::mount(array_merge([
            'role' => 'admin',
            'showRestore' => false,
            'printRoute' => 'admin.print.vehicles',
            'minUserType' => 1,
        ], $config));
    }
}
