@props([
    'currentRoute', // passed from Livewire mount
])

@php
    // Determine if the "Data Management" dropdown should be active
    $dataManagementRoutes = ['admin.guards', 'admin.drivers', 'admin.plate-numbers', 'admin.locations'];
    $isDataManagementActive = in_array($currentRoute, $dataManagementRoutes);
@endphp

<div class="flex items-center gap-2">
    {{-- Dashboard --}}
    <x-navigation.horizontal-menu-item href="{{ route('admin.dashboard') }}" :active="$currentRoute === 'admin.dashboard'"
        icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>'>
        Dashboard
    </x-navigation.horizontal-menu-item>

    {{-- Data Management Dropdown --}}
    <x-navigation.horizontal-menu-dropdown label="Data Management" :active="$isDataManagementActive"
        icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
            </svg>'>
        <x-navigation.sidebar-menu-item href="{{ route('admin.guards') }}" :active="$currentRoute === 'admin.guards'"
            icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>'
            :indent="true">
            Guards
        </x-navigation.sidebar-menu-item>

        <x-navigation.sidebar-menu-item href="{{ route('admin.drivers') }}" :active="$currentRoute === 'admin.drivers'"
            icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>'
            :indent="true">
            Drivers
        </x-navigation.sidebar-menu-item>

        <x-navigation.sidebar-menu-item href="{{ route('admin.plate-numbers') }}" :active="$currentRoute === 'admin.plate-numbers'"
            icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>'
            :indent="true">
            Plate Numbers
        </x-navigation.sidebar-menu-item>

        <x-navigation.sidebar-menu-item href="{{ route('admin.locations') }}" :active="$currentRoute === 'admin.locations'"
            icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>'
            :indent="true">
            Locations
        </x-navigation.sidebar-menu-item>
    </x-navigation.horizontal-menu-dropdown>

    {{-- Trucks --}}
    <x-navigation.horizontal-menu-item href="{{ route('admin.trucks') }}" :active="$currentRoute === 'admin.trucks'"
        icon='<img src="https://cdn-icons-png.flaticon.com/512/605/605863.png" alt="Trucks" class="w-5 h-5 object-contain" />'>
        Trucks
    </x-navigation.horizontal-menu-item>

    {{-- Reports --}}
    <x-navigation.horizontal-menu-item href="{{ route('admin.reports') }}" :active="$currentRoute === 'admin.reports'"
        icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
            </svg>'>
        Reports
    </x-navigation.horizontal-menu-item>

    {{-- Audit Trail --}}
    <x-navigation.horizontal-menu-item href="{{ route('admin.audit-trail') }}" :active="$currentRoute === 'admin.audit-trail'"
        icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>'>
        Audit Trail
    </x-navigation.horizontal-menu-item>
</div>

