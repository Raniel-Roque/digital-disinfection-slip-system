<?php

namespace App\Livewire\Admin;

use App\Livewire\Shared\Locations as SharedLocations;

class Locations extends SharedLocations
{
    public function mount($config = [])
    {
        parent::mount(array_merge([
            'role' => 'admin',
            'showRestore' => false,
            'printRoute' => 'admin.print.locations',
            'minUserType' => 1,
        ], $config));
    }
}
