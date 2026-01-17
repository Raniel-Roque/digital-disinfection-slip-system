<?php

namespace App\Livewire\SuperAdmin;

use App\Livewire\Shared\Drivers as SharedDrivers;

class Drivers extends SharedDrivers
{
    public function mount($config = [])
    {
        parent::mount(array_merge([
            'role' => 'superadmin',
            'showRestore' => true,
            'printRoute' => 'superadmin.print.drivers',
            'minUserType' => 2,
        ], $config));
    }
}
