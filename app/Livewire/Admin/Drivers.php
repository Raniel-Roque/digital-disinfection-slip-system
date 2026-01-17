<?php

namespace App\Livewire\Admin;

use App\Livewire\Shared\Drivers as SharedDrivers;

class Drivers extends SharedDrivers
{
    public function mount($config = [])
    {
        parent::mount(array_merge([
            'role' => 'admin',
            'showRestore' => false,
            'printRoute' => 'admin.print.drivers',
            'minUserType' => 1,
        ], $config));
    }
}
