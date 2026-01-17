<?php

namespace App\Livewire\SuperAdmin;

use App\Livewire\Shared\Vehicles as SharedVehicles;

class Vehicles extends SharedVehicles
{
    public function mount($config = [])
    {
        parent::mount(array_merge([
            'role' => 'superadmin',
            'showRestore' => true,
            'printRoute' => 'superadmin.print.vehicles',
            'minUserType' => 2,
        ], $config));
    }
}
