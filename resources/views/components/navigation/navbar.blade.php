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

<nav x-data="{ open: false, userMenuOpen: false }" class="bg-[#ffb97f] shadow-md rounded-md px-2 sm:px-4 py-2 sm:py-3">
    <!-- Mobile: Simple Layout - Hamburger + Logo + Farm Name + User Menu -->
    <div class="flex items-center justify-between gap-3 sm:hidden">
        <button type="button"
            class="hover:cursor-pointer shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-full bg-white border-2 border-gray-200 text-gray-700 shadow-md hover:bg-[#EC8B18] hover:border-[#EC8B18] hover:text-white hover:shadow-lg hover:scale-105 focus:outline-none focus:ring-2 focus:ring-[#EC8B18] focus:ring-offset-2 transition-all duration-300 cursor-pointer"
            x-on:click="userMenuOpen = false; open = true" aria-label="Open menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>
        <div class="flex items-center gap-2.5 flex-1 justify-center min-w-0">
            <img src="{{ asset('storage/images/logo/BGC.png') }}" alt="Logo" class="h-10 w-10 object-contain shrink-0">
            <span class="font-bold text-gray-800 text-base truncate">{{ $locationName }}</span>
        </div>
        <!-- User Menu Button (Mobile) -->
        <div class="relative shrink-0" @click.away="userMenuOpen = false">
            <button type="button" @click="open = false; userMenuOpen = !userMenuOpen"
                class="hover:cursor-pointer inline-flex items-center justify-center w-10 h-10 rounded-full bg-white border-2 border-gray-200 text-gray-700 shadow-md hover:bg-[#EC8B18] hover:border-[#EC8B18] hover:text-white hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-[#EC8B18] focus:ring-offset-2 transition-all duration-300 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" @click="userMenuOpen = false"
                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors text-left">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Desktop: Horizontal Layout -->
    <div class="hidden sm:flex items-center justify-between gap-3">
        <!-- Left: Hamburger + Logo + Farm Name + Date -->
        <div class="flex items-center gap-3 min-w-0">
            <button type="button"
                class="hover:cursor-pointer shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-full bg-white border-2 border-gray-200 text-gray-700 shadow-md hover:bg-[#EC8B18] hover:border-[#EC8B18] hover:text-white hover:shadow-lg hover:scale-105 focus:outline-none focus:ring-2 focus:ring-[#EC8B18] focus:ring-offset-2 transition-all duration-300 cursor-pointer"
                x-on:click="userMenuOpen = false; open = true" aria-label="Open menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            <div class="flex items-center gap-3 transition-all duration-200 min-w-0" :class="open ? 'lg:ml-60' : ''">
                <!-- Logo (spans both rows) -->
                <img src="{{ asset('storage/images/logo/BGC.png') }}" alt="Logo" class="h-12 w-12 object-contain shrink-0">
                <!-- Farm Name + Date (stacked) -->
                <div class="flex flex-col">
                    <span class="font-semibold text-gray-800 text-lg truncate">{{ $locationName }}</span>
                    <span class="text-sm text-gray-600">{{ now()->format('F d, Y') }}</span>
                </div>
            </div>
        </div>

        <!-- Right: Landing Button + User Menu -->
        <div class="flex items-center gap-3 shrink-0 ml-auto">
            <x-buttons.nav-button href="{{ url('/') }}">
                <span class="hidden lg:inline">Go to Landing</span>
                <span class="lg:hidden">Landing</span>
            </x-buttons.nav-button>
            {{ $slot }}
            
            <!-- User Menu Button (Desktop) -->
            <div class="relative" @click.away="userMenuOpen = false">
                <button type="button" @click="open = false; userMenuOpen = !userMenuOpen"
                    class="hover:cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-gray-200 text-gray-700 shadow-sm hover:bg-gray-50 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#EC8B18] focus:ring-offset-2 transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="hidden md:inline font-medium text-sm max-w-[120px] truncate">{{ $userName }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
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
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" @click="userMenuOpen = false"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors text-left">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar / Drawer -->
    <div class="fixed inset-0 z-40 pointer-events-none" x-cloak>
        <!-- Panel -->
        <div class="absolute top-0 left-0 h-full w-72 max-w-full bg-[#ffb97f] border-r-2 border-black shadow-xl rounded-r-2xl p-6 flex flex-col gap-6 pointer-events-auto"
            x-show="open" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full" x-on:click.away="userMenuOpen = false; open = false">
            <!-- Menu Header -->
            <div class="bg-gray-100/90 shadow-md rounded-md p-4 flex items-center justify-between gap-4">
                <div class="flex flex-col flex-1 min-w-0">
                    <span class="font-semibold text-gray-800 text-base">Menu</span>
                </div>
                <button type="button"
                    class="hover:cursor-pointer shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-full bg-white border-2 border-gray-200 text-gray-700 shadow-md hover:bg-[#EC8B18] hover:border-[#EC8B18] hover:text-white hover:shadow-lg hover:scale-105 focus:outline-none focus:ring-2 focus:ring-[#EC8B18] focus:ring-offset-2 transition-all duration-300 cursor-pointer"
                    x-on:click="userMenuOpen = false; open = false" aria-label="Close menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modules Section / Sidebar -->
            <div class="bg-white/50 rounded-lg p-3 shadow-sm border border-gray-200">
                <div class="space-y-1">
                    @switch(auth()->user()->user_type)
                        @case(0)
                            <livewire:sidebar.sidebar-user :currentRoute="Route::currentRouteName()" />
                        @break

                        @case(1)
                            <livewire:sidebar.sidebar-admin :currentRoute="Route::currentRouteName()" />
                        @break

                        @case(2)
                            <livewire:sidebar.sidebar-superadmin :currentRoute="Route::currentRouteName()" />
                        @break
                    @endswitch
                </div>
            </div>

            <!-- Landing Button (Mobile Only) -->
            <div class="bg-white/50 rounded-lg p-3 shadow-sm border border-gray-200">
                <a href="{{ url('/') }}"
                    class="sm:hidden hover:cursor-pointer w-full rounded-lg px-3 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 hover:shadow-md hover:scale-[1.02] focus:ring-2 focus:ring-blue-500 transition-all duration-200 cursor-pointer flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Go to Landing</span>
                </a>
            </div>
        </div>
    </div>
</nav>
