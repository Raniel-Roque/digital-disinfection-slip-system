<?php

namespace App\Livewire\SuperAdmin;

use App\Livewire\Shared\Locations as SharedLocations;

class Locations extends SharedLocations
{
    public function mount($config = [])
    {
        parent::mount(array_merge([
            'role' => 'superadmin',
            'showRestore' => true,
            'printRoute' => 'superadmin.print.locations',
            'minUserType' => 2,
        ], $config));
    }
}
