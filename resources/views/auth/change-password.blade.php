<x-layout>
    <x-navigation.navbar module="Change Password">
        <x-slot:sidebar>
            @switch(auth()->user()->user_type)
                @case(0)
                    <livewire:sidebar.sidebar-user :currentRoute="Route::currentRouteName()" />
                @break

                @case(1)
                    <livewire:sidebar.sidebar-admin />
                @break

                @case(2)
                    <livewire:sidebar.sidebar-super-admin />
                @break
            @endswitch
        </x-slot:sidebar>
    </x-navigation.navbar>
    <livewire:sidebar.change-password />
</x-layout>
