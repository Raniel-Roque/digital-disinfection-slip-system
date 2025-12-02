<x-layout>
    <x-navbar module="Change Password">
        <x-slot:sidebar>
            @switch(auth()->user()->user_type)
                @case(0)
                    <livewire:sidebar-user :currentRoute="Route::currentRouteName()" />
                @break

                @case(1)
                    <livewire:sidebar-admin />
                @break

                @case(2)
                    <livewire:sidebar-super-admin />
                @break
            @endswitch
        </x-slot:sidebar>
    </x-navbar>
    <livewire:change-password />
</x-layout>
