@props([
    'currentRoute',
])

@php
    $userType = auth()->user()->effectiveUserType();
    
    // Determine active states
    $trucksRoutes = ['user.incoming-trucks', 'user.outgoing-trucks', 'user.completed-trucks'];
    $isDashboardActive = $currentRoute === 'user.dashboard' || 
                         $currentRoute === 'admin.dashboard' || 
                         $currentRoute === 'superadmin.dashboard';
    $isTrucksActive = in_array($currentRoute, $trucksRoutes);
    $isReportsActive = $currentRoute === 'user.reports' || 
                       $currentRoute === 'admin.reports' || 
                       $currentRoute === 'superadmin.reports';
    
    // Data Management routes for Admin
    $adminDataManagementRoutes = ['admin.guards', 'admin.drivers', 'admin.plate-numbers', 'admin.locations'];
    $adminDataManagementActive = in_array($currentRoute, $adminDataManagementRoutes);
    
    // Data Management routes for Superadmin
    $superadminDataManagementRoutes = [
        'superadmin.guards',
        'superadmin.admins',
        'superadmin.drivers',
        'superadmin.plate-numbers',
        'superadmin.locations',
    ];
    $superadminDataManagementActive = in_array($currentRoute, $superadminDataManagementRoutes);
@endphp

@if($userType === 0)
    {{-- Guards/User Bottom Navigation --}}
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50 sm:hidden">
        <div class="grid grid-cols-5 items-center h-16">
            <x-navigation.bottom-nav-item 
                href="{{ route('user.dashboard') }}" 
                :active="$isDashboardActive"
                icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>'>
                Dashboard
            </x-navigation.bottom-nav-item>

            <x-navigation.bottom-nav-item 
                href="{{ route('user.incoming-trucks') }}" 
                :active="$currentRoute === 'user.incoming-trucks'"
                icon='<img src="https://cdn-icons-png.flaticon.com/512/8591/8591505.png" alt="Incoming" class="w-6 h-6 object-contain" />'>
                Incoming
            </x-navigation.bottom-nav-item>

            <x-navigation.bottom-nav-item 
                href="{{ route('user.outgoing-trucks') }}" 
                :active="$currentRoute === 'user.outgoing-trucks'"
                icon='<img src="https://cdn-icons-png.flaticon.com/512/7468/7468319.png" alt="Outgoing" class="w-6 h-6 object-contain" />'>
                Outgoing
            </x-navigation.bottom-nav-item>

            <x-navigation.bottom-nav-item 
                href="{{ route('user.completed-trucks') }}" 
                :active="$currentRoute === 'user.completed-trucks'"
                icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>'>
                Completed
            </x-navigation.bottom-nav-item>

            <x-navigation.bottom-nav-item 
                href="{{ route('user.reports') }}" 
                :active="$isReportsActive"
                icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                    </svg>'>
                Reports
            </x-navigation.bottom-nav-item>
        </div>
    </nav>
@elseif($userType === 1)
    {{-- Admin Bottom Navigation --}}
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50 sm:hidden">
        <div class="grid grid-cols-5 items-center h-16">
            <x-navigation.bottom-nav-item 
                href="{{ route('admin.dashboard') }}" 
                :active="$isDashboardActive"
                icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>'>
                Dashboard
            </x-navigation.bottom-nav-item>

            <x-navigation.mobile-dropup 
                label="Data" 
                :active="$adminDataManagementActive"
                icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                    </svg>'>
                <x-navigation.mobile-dropup-item 
                    href="{{ route('admin.guards') }}" 
                    :active="$currentRoute === 'admin.guards'"
                    icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>'>
                    Guards
                </x-navigation.mobile-dropup-item>

                <x-navigation.mobile-dropup-item 
                    href="{{ route('admin.drivers') }}" 
                    :active="$currentRoute === 'admin.drivers'"
                    icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>'>
                    Drivers
                </x-navigation.mobile-dropup-item>

                <x-navigation.mobile-dropup-item 
                    href="{{ route('admin.plate-numbers') }}" 
                    :active="$currentRoute === 'admin.plate-numbers'"
                    icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>'>
                    Plate Numbers
                </x-navigation.mobile-dropup-item>

                <x-navigation.mobile-dropup-item 
                    href="{{ route('admin.locations') }}" 
                    :active="$currentRoute === 'admin.locations'"
                    icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>'>
                    Locations
                </x-navigation.mobile-dropup-item>
            </x-navigation.mobile-dropup>

            <x-navigation.bottom-nav-item 
                href="{{ route('admin.trucks') }}" 
                :active="$currentRoute === 'admin.trucks'"
                icon='<img src="https://cdn-icons-png.flaticon.com/512/605/605863.png" alt="Trucks" class="w-6 h-6 object-contain" />'>
                Trucks
            </x-navigation.bottom-nav-item>

            <x-navigation.bottom-nav-item 
                href="{{ route('admin.reports') }}" 
                :active="$isReportsActive"
                icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                    </svg>'>
                Reports
            </x-navigation.bottom-nav-item>

            <x-navigation.bottom-nav-item 
                href="{{ route('admin.audit-trail') }}" 
                :active="$currentRoute === 'admin.audit-trail'"
                icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>'>
                Audit
            </x-navigation.bottom-nav-item>
        </div>
    </nav>
@elseif($userType === 2)
    {{-- Superadmin Bottom Navigation --}}
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50 sm:hidden">
        <div class="grid grid-cols-6 items-center h-16">
            <x-navigation.bottom-nav-item 
                href="{{ route('superadmin.dashboard') }}" 
                :active="$isDashboardActive"
                icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>'>
                Dashboard
            </x-navigation.bottom-nav-item>

            <x-navigation.mobile-dropup 
                label="Data" 
                :active="$superadminDataManagementActive"
                icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                    </svg>'>
                <x-navigation.mobile-dropup-item 
                    href="{{ route('superadmin.guards') }}" 
                    :active="$currentRoute === 'superadmin.guards'"
                    icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>'>
                    Guards
                </x-navigation.mobile-dropup-item>

                <x-navigation.mobile-dropup-item 
                    href="{{ route('superadmin.admins') }}" 
                    :active="$currentRoute === 'superadmin.admins'"
                    icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>'>
                    Admins
                </x-navigation.mobile-dropup-item>

                <x-navigation.mobile-dropup-item 
                    href="{{ route('superadmin.drivers') }}" 
                    :active="$currentRoute === 'superadmin.drivers'"
                    icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>'>
                    Drivers
                </x-navigation.mobile-dropup-item>

                <x-navigation.mobile-dropup-item 
                    href="{{ route('superadmin.plate-numbers') }}" 
                    :active="$currentRoute === 'superadmin.plate-numbers'"
                    icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>'>
                    Plate Numbers
                </x-navigation.mobile-dropup-item>

                <x-navigation.mobile-dropup-item 
                    href="{{ route('superadmin.locations') }}" 
                    :active="$currentRoute === 'superadmin.locations'"
                    icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>'>
                    Locations
                </x-navigation.mobile-dropup-item>
            </x-navigation.mobile-dropup>

            <x-navigation.bottom-nav-item 
                href="{{ route('superadmin.trucks') }}" 
                :active="$currentRoute === 'superadmin.trucks'"
                icon='<img src="https://cdn-icons-png.flaticon.com/512/605/605863.png" alt="Trucks" class="w-6 h-6 object-contain" />'>
                Trucks
            </x-navigation.bottom-nav-item>

            <x-navigation.bottom-nav-item 
                href="{{ route('superadmin.reports') }}" 
                :active="$isReportsActive"
                icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                    </svg>'>
                Reports
            </x-navigation.bottom-nav-item>

            <x-navigation.bottom-nav-item 
                href="{{ route('superadmin.audit-trail') }}" 
                :active="$currentRoute === 'superadmin.audit-trail'"
                icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>'>
                Audit
            </x-navigation.bottom-nav-item>

            <x-navigation.bottom-nav-item 
                href="{{ route('superadmin.settings') }}" 
                :active="$currentRoute === 'superadmin.settings'"
                icon='<svg xmlns="https://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>'>
                Settings
            </x-navigation.bottom-nav-item>
        </div>
    </nav>
@endif

