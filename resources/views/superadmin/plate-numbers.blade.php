<x-layout>
    <x-navigation.navbar module="Plate Numbers">
        <x-slot:sidebar>
            <livewire:sidebar.sidebar-superadmin :currentRoute="Route::currentRouteName()" />
        </x-slot:sidebar>
    </x-navigation.navbar>

    <livewire:superadmin.plate-numbers />
</x-layout>
