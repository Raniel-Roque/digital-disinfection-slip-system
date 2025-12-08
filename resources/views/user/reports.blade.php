<x-layout>
    <x-navigation.navbar module="My Reports">
        <x-slot:sidebar>
            <livewire:sidebar.sidebar-user :currentRoute="Route::currentRouteName()" />
        </x-slot:sidebar>
    </x-navigation.navbar>

    <livewire:user.reports />
</x-layout>
