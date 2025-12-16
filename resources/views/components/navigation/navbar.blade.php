@props([
    'module' => 'Dashboard',
])

@php
    // Get location name from session if user is logged in at a location, otherwise default to Brookside
    $locationName = session('location_name') ?? 'Brookside';
    // Get user's full name
    $userName = auth()->user()->first_name;
    if(auth()->user()->middle_name) {
        $userName .= ' ' . strtoupper(substr(auth()->user()->middle_name, 0, 1)) . '.';
    }
    $userName .= ' ' . auth()->user()->last_name;
@endphp

<nav x-data="{ userMenuOpen: false, showLogoutConfirm: false, isLoggingOut: false }" class="bg-[#ffb97f] shadow-md rounded-md px-2 sm:px-4 py-2 sm:py-3">
    <!-- Mobile: Simple Layout - Logo + Farm Name + User Menu -->
    <div class="flex items-center justify-between gap-3 sm:hidden">
        <a href="{{ route(auth()->user()->dashboardRoute()) }}" class="flex items-center gap-2.5 min-w-0 hover:opacity-80 transition-opacity">
            <img src="{{ asset('storage/images/logo/BGC.png') }}" alt="Logo" class="h-10 w-10 object-contain shrink-0">
            <div class="flex flex-col">
                <span class="font-semibold text-gray-800 text-base truncate">{{ $locationName }}</span>
                <span class="text-xs text-gray-600">{{ now()->format('F d, Y') }}</span>
            </div>
        </a>
        <!-- User Menu Button (Mobile) -->
        <div class="relative shrink-0" @click.away="userMenuOpen = false">
            <button type="button" @click="userMenuOpen = !userMenuOpen"
                class="hover:cursor-pointer inline-flex items-center justify-center w-10 h-10 rounded-full bg-white border-2 border-gray-200 text-gray-700 shadow-md hover:bg-[#EC8B18] hover:border-[#EC8B18] hover:text-white hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-[#EC8B18] focus:ring-offset-2 transition-all duration-300 cursor-pointer">
                <svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </button>
            <!-- User Dropdown Menu (Mobile) -->
            <div x-show="userMenuOpen" x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
                style="display: none;">
                <!-- User Info -->
                <div class="px-4 py-3 border-b border-gray-200">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $userName }}</p>
                    <p class="text-xs text-gray-500 truncate"><span>@</span>{{ auth()->user()->username ?? 'username' }}</p>
                </div>
                <!-- Menu Items -->
                <a href="{{ url('/') }}" @click="userMenuOpen = false"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Go to Landing</span>
                </a>
                @if (auth()->user()->user_type === 0)
                    <a href="{{ route('user.report') }}" @click="userMenuOpen = false"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                        </svg>
                        <span>Report</span>
                    </a>
                @endif
                <a href="{{ route('password.change') }}" @click="userMenuOpen = false"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    <span>Change Password</span>
                </a>
                <button type="button" @click="userMenuOpen = false; showLogoutConfirm = true" :disabled="isLoggingOut"
                    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors text-left cursor-pointer hover:cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Logout</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Desktop: Horizontal Layout -->
    <div class="hidden sm:flex items-center justify-between gap-3">
        <!-- Left: Logo + Farm Name + Date -->
        <a href="{{ route(auth()->user()->dashboardRoute()) }}" class="flex items-center gap-3 min-w-0 hover:opacity-80 transition-opacity">
                <img src="{{ asset('storage/images/logo/BGC.png') }}" alt="Logo" class="h-12 w-12 object-contain shrink-0">
                <!-- Farm Name + Date (stacked) -->
                <div class="flex flex-col">
                    <span class="font-semibold text-gray-800 text-lg truncate">{{ $locationName }}</span>
                    <span class="text-sm text-gray-600">{{ now()->format('F d, Y') }}</span>
            </div>
        </a>

        <!-- Right: User Menu -->
        <div class="flex items-center gap-3 shrink-0 ml-auto">
            {{ $slot }}
            
            <!-- User Menu Button (Desktop) -->
            <div class="relative" @click.away="userMenuOpen = false">
                <button type="button" @click="open = false; userMenuOpen = !userMenuOpen"
                    class="cursor-pointer hover:cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-gray-200 text-gray-700 shadow-sm hover:bg-gray-50 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#EC8B18] focus:ring-offset-2 transition-all duration-200">
                    <svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="hidden md:inline font-medium text-sm max-w-[120px] truncate">{{ $userName }}</span>
                    <svg xmlns="https://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                        :class="{ 'rotate-180': userMenuOpen }" style="transition: transform 0.2s;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <!-- User Dropdown Menu (Desktop) -->
                <div x-show="userMenuOpen" x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
                    style="display: none;">
                    <!-- User Info -->
                    <div class="px-4 py-3 border-b border-gray-200">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $userName }}</p>
                        <p class="text-xs text-gray-500 truncate"><span>@</span>{{ auth()->user()->username ?? 'username' }}</p>
                    </div>
                    <!-- Menu Items -->
                    <a href="{{ url('/') }}" @click="userMenuOpen = false"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Go to Landing</span>
                    </a>
                    @if (auth()->user()->user_type === 0)
                        <a href="{{ route('user.report') }}" @click="userMenuOpen = false"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                            </svg>
                            <span>Report</span>
                        </a>
                    @endif
                    <a href="{{ route('password.change') }}" @click="userMenuOpen = false"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        <span>Change Password</span>
                    </a>
                    <button type="button" @click="userMenuOpen = false; showLogoutConfirm = true" :disabled="isLoggingOut"
                    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors text-left cursor-pointer hover:cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Logout</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Logout Confirmation Modal (Outside dropdown, shared for mobile and desktop) -->
    <div x-show="showLogoutConfirm" x-cloak
        class="fixed inset-0 z-100 flex items-center justify-center bg-black/80"
        @click.self="showLogoutConfirm = false"
        style="display: none;">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Confirm Logout</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to logout?</p>
            <form method="POST" action="{{ route('logout') }}" @submit="isLoggingOut = true">
                @csrf
                <div class="flex justify-end gap-3">
                    <button type="button" @click="showLogoutConfirm = false" :disabled="isLoggingOut"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors cursor-pointer hover:cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isLoggingOut"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer hover:cursor-pointer">
                        <span x-text="isLoggingOut ? 'Logging out...' : 'Logout'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</nav>

<!-- Horizontal Menu Bar (Desktop Only) -->
<div class="hidden sm:block bg-white shadow-sm border-b border-gray-200">
    <div class="px-4 py-2" style="overflow-x: auto;">
        <div class="flex items-center gap-2 min-w-max">
                    @switch(auth()->user()->user_type)
                        @case(0)
                    @include('livewire.sidebar.horizontal-menu-user', ['currentRoute' => Route::currentRouteName()])
                        @break

                        @case(1)
                    @include('livewire.sidebar.horizontal-menu-admin', ['currentRoute' => Route::currentRouteName()])
                        @break

                        @case(2)
                    @include('livewire.sidebar.horizontal-menu-superadmin', ['currentRoute' => Route::currentRouteName()])
                        @break
                    @endswitch
        </div>
    </div>
</div>

<!-- Mobile Bottom Navigation -->
<x-navigation.mobile-bottom-nav :currentRoute="Route::currentRouteName()" />
