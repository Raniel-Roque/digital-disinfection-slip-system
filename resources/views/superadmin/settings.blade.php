<x-layout>
    <x-navigation.navbar module="Settings">
        <x-slot:sidebar>
            <livewire:sidebar.sidebar-superadmin :currentRoute="Route::currentRouteName()" />
        </x-slot:sidebar>
    </x-navigation.navbar>

    <livewire:superadmin.settings />
</x-layout>
