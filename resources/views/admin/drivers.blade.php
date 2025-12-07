<x-layout>
    <x-navigation.navbar module="Drivers">
        <x-slot:sidebar>
            <livewire:sidebar.sidebar-admin :currentRoute="Route::currentRouteName()" />
        </x-slot:sidebar>
    </x-navigation.navbar>

    <livewire:admin.drivers />
</x-layout>
