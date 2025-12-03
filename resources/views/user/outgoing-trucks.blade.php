<x-layout>
    <x-navigation.navbar module="Outgoing Trucks">
        <x-slot:sidebar>
            <livewire:sidebar.sidebar-user :currentRoute="Route::currentRouteName()" />
        </x-slot:sidebar>
    </x-navigation.navbar>

    <livewire:trucks.truck-list type="outgoing" />
</x-layout>
