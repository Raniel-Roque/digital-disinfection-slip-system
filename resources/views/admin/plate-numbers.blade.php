<x-layout>
    <x-navigation.navbar module="Plate Numbers">
        <x-slot:sidebar>
            <livewire:sidebar.sidebar-admin :currentRoute="Route::currentRouteName()" />
        </x-slot:sidebar>
    </x-navigation.navbar>

    <livewire:admin.plate-numbers />
</x-layout>
