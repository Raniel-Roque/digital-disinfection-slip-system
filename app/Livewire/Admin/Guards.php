<?php

namespace App\Livewire\Admin;

use App\Livewire\Shared\Guards as SharedGuards;

class Guards extends SharedGuards
{
    public function mount($config = [])
    {
        parent::mount(array_merge([
            'role' => 'admin',
            'showGuardTypeFilter' => true,
            'showSuperGuardEdit' => true,
            'showRestore' => false,
            'excludeSuperGuards' => false,
            'excludeCurrentUser' => false,
            'printRoute' => 'admin.print.guards',
        ], $config));
    }
}
