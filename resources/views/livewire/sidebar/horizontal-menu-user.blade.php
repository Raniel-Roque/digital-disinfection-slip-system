@props([
    'currentRoute', // passed from Livewire mount
])

@php
    // Determine if the "Trucks" dropdown should be active
    $trucksRoutes = ['user.incoming-trucks', 'user.outgoing-trucks', 'user.completed-trucks'];
    $isTrucksActive = in_array($currentRoute, $trucksRoutes);
@endphp

<div class="flex items-center gap-2">
    {{-- Dashboard --}}
    <x-navigation.horizontal-menu-item href="{{ route('user.dashboard') }}" :active="$currentRoute === 'user.dashboard'"
        icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>'>
        Dashboard
    </x-navigation.horizontal-menu-item>

    {{-- Trucks Dropdown --}}
    <x-navigation.horizontal-menu-dropdown label="Trucks" :active="$isTrucksActive"
        icon='<img src="https://cdn-icons-png.flaticon.com/512/605/605863.png" alt="Trucks" class="w-5 h-5 object-contain" />'>
        <x-navigation.sidebar-menu-item href="{{ route('user.incoming-trucks') }}" :active="$currentRoute === 'user.incoming-trucks'"
            icon='<img src="https://cdn-icons-png.flaticon.com/512/8591/8591505.png" alt="Incoming" class="w-5 h-5 object-contain" />'
            :indent="true">
            Incoming Trucks
        </x-navigation.sidebar-menu-item>

        <x-navigation.sidebar-menu-item href="{{ route('user.outgoing-trucks') }}" :active="$currentRoute === 'user.outgoing-trucks'"
            icon='<img src="https://cdn-icons-png.flaticon.com/512/7468/7468319.png" alt="Outgoing" class="w-5 h-5 object-contain" />'
            :indent="true">
            Outgoing Trucks
        </x-navigation.sidebar-menu-item>

        <x-navigation.sidebar-menu-item href="{{ route('user.completed-trucks') }}" :active="$currentRoute === 'user.completed-trucks'"
            icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>'
            :indent="true">
            Completed Trucks
        </x-navigation.sidebar-menu-item>
    </x-navigation.horizontal-menu-dropdown>

    {{-- Reports --}}
    <x-navigation.horizontal-menu-item href="{{ route('user.reports') }}" :active="$currentRoute === 'user.reports'"
        icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
            </svg>'>
        Reports
    </x-navigation.horizontal-menu-item>
</div>

