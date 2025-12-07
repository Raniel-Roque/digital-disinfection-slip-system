<x-layout>
    <x-navigation.navbar module="Drivers">
        <x-slot:sidebar>
            <livewire:sidebar.sidebar-superadmin :currentRoute="Route::currentRouteName()" />
        </x-slot:sidebar>
    </x-navigation.navbar>

    <livewire:superadmin.drivers />
</x-layout>
