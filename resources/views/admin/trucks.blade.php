<x-layout>
    <x-navigation.navbar module="Trucks">
        <x-slot:sidebar>
            <livewire:sidebar.sidebar-admin :currentRoute="Route::currentRouteName()" />
        </x-slot:sidebar>
    </x-navigation.navbar>

    <livewire:admin.trucks />
</x-layout>
