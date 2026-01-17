<?php

namespace App\Livewire\User\Data;

use App\Livewire\Shared\Guards as SharedGuards;

class Guards extends SharedGuards
{
    public function mount($config = [])
    {
        parent::mount(array_merge([
            'role' => 'superguard',
            'showGuardTypeFilter' => false,
            'showSuperGuardEdit' => false,
            'showRestore' => false,
            'excludeSuperGuards' => true,
            'excludeCurrentUser' => true,
            'printRoute' => 'user.print.guards',
        ], $config));
    }
}
