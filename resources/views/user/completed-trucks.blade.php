<x-layout>
    <x-navbar module="Completed Trucks">
        <x-slot:sidebar>
            <livewire:sidebar-user :currentRoute="Route::currentRouteName()" />
        </x-slot:sidebar>
    </x-navbar>

    <livewire:truck-list-completed />
</x-layout>
