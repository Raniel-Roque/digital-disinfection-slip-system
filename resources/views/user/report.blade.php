<x-layout>
    <x-navigation.navbar module="Report Issue">
        <x-slot:sidebar>
            <livewire:sidebar.sidebar-user :currentRoute="Route::currentRouteName()" />
        </x-slot:sidebar>
    </x-navigation.navbar>

    <livewire:user.misc-report />
</x-layout>
