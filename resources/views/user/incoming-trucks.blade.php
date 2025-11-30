<x-layout>
    <x-navbar module="Incoming Trucks">
        <x-slot:sidebar>
            @livewire('sidebar-user', ['currentRoute' => request()->route()->getName()])
        </x-slot:sidebar>
    </x-navbar>
</x-layout>
