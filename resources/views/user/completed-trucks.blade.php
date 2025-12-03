<x-layout>
    <x-navigation.navbar module="Completed Trucks">
        <x-slot:sidebar>
            <livewire:sidebar-user :currentRoute="Route::currentRouteName()" />
        </x-slot:sidebar>
    </x-navigation.navbar>

    <livewire:trucks.trucks.truck-list-completed />
</x-layout>
