<x-layout>
    <x-navigation.navbar module="Guards">
        <x-slot:sidebar>
            <livewire:sidebar.sidebar-superadmin :currentRoute="Route::currentRouteName()" />
        </x-slot:sidebar>
    </x-navigation.navbar>

    <livewire:superadmin.guards />
</x-layout>
