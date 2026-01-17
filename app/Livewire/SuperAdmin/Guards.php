<?php

namespace App\Livewire\SuperAdmin;

use App\Livewire\Shared\Guards as SharedGuards;

class Guards extends SharedGuards
{
    public function mount($config = [])
    {
        parent::mount(array_merge([
            'role' => 'superadmin',
            'showGuardTypeFilter' => true,
            'showSuperGuardEdit' => true,
            'showRestore' => true,
            'excludeSuperGuards' => false,
            'excludeCurrentUser' => false,
            'printRoute' => 'superadmin.print.guards',
        ], $config));
    }
}
