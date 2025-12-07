<x-layout>
    <x-navigation.navbar module="Locations">
        <x-slot:sidebar>
            <livewire:sidebar.sidebar-superadmin :currentRoute="Route::currentRouteName()" />
        </x-slot:sidebar>
    </x-navigation.navbar>

    <livewire:superadmin.locations />
</x-layout>
