@props([
    'module' => 'Dashboard',
])

@php
    // Get location name from session if user is logged in at a location, otherwise default to Brookside
    $locationName = session('location_name') ?? 'Brookside';
@endphp

<nav x-data="{ open: false }" class="bg-[#FFDBBD] shadow-md rounded-md px-2 sm:px-4 py-2 sm:py-3">
    <!-- Mobile: Simple Layout - Hamburger + Logo + Farm Name -->
    <div class="flex items-center justify-between gap-3 sm:hidden">
        <button type="button"
            class="hover:cursor-pointer shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-full bg-white border-2 border-gray-200 text-gray-700 shadow-md hover:bg-[#EC8B18] hover:border-[#EC8B18] hover:text-white hover:shadow-lg hover:scale-105 focus:outline-none focus:ring-2 focus:ring-[#EC8B18] focus:ring-offset-2 transition-all duration-300 cursor-pointer"
            x-on:click="open = true" aria-label="Open menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>
        <div class="flex items-center gap-2.5 flex-1 justify-center min-w-0">
            <img src="{{ asset('storage/images/logo/BGC.png') }}" alt="Logo" class="h-10 w-10 object-contain shrink-0">
            <span class="font-bold text-gray-800 text-base truncate">{{ $locationName }}</span>
        </div>
        <div class="w-10"></div> <!-- Spacer to center the logo/name -->
    </div>

    <!-- Desktop: Horizontal Layout -->
    <div class="hidden sm:flex items-center justify-between gap-3">
        <!-- Left: Hamburger + Logo + Farm Name + Date -->
        <div class="flex items-center gap-3 min-w-0">
            <button type="button"
                class="hover:cursor-pointer shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-full bg-white border-2 border-gray-200 text-gray-700 shadow-md hover:bg-[#EC8B18] hover:border-[#EC8B18] hover:text-white hover:shadow-lg hover:scale-105 focus:outline-none focus:ring-2 focus:ring-[#EC8B18] focus:ring-offset-2 transition-all duration-300 cursor-pointer"
                x-on:click="open = true" aria-label="Open menu">
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

        <!-- Right: Slot for actions -->
        <div class="flex items-center gap-3 shrink-0 ml-auto">
            <x-buttons.nav-button href="{{ url('/') }}">
                <span class="hidden lg:inline">Go to Landing</span>
                <span class="lg:hidden">Landing</span>
            </x-buttons.nav-button>
            {{ $slot }}
        </div>
    </div>

    <!-- Sidebar / Drawer -->
    <div class="fixed inset-0 z-40 pointer-events-none" x-cloak>
        <!-- Panel -->
        <div class="absolute top-0 left-0 h-full w-72 max-w-full bg-[#ffd4b0] border-r-2 border-black shadow-xl rounded-r-2xl p-6 flex flex-col gap-6 pointer-events-auto"
            x-show="open" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full" x-on:click.away="open = false">
            <!-- Menu Header -->
            <div class="bg-gray-100/90 shadow-md rounded-md p-4 flex items-center justify-between gap-4">
                <div class="flex flex-col flex-1 min-w-0">
                    <span class="font-semibold text-gray-800 text-base wrap-break-word leading-tight">
                        {{ auth()->user()->first_name }}@if(auth()->user()->middle_name) {{ strtoupper(substr(auth()->user()->middle_name, 0, 1)) }}.@endif {{ auth()->user()->last_name }}
                    </span>
                    <span
                        class="text-sm text-gray-500 wrap-break-word mt-0.5">{{ '@' . (auth()->user()->username ?? 'username') }}</span>
                </div>
                <button type="button"
                    class="hover:cursor-pointer shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-full bg-white border-2 border-gray-200 text-gray-700 shadow-md hover:bg-[#EC8B18] hover:border-[#EC8B18] hover:text-white hover:shadow-lg hover:scale-105 focus:outline-none focus:ring-2 focus:ring-[#EC8B18] focus:ring-offset-2 transition-all duration-300 cursor-pointer"
                    x-on:click="open = false" aria-label="Close menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Divider -->
            <hr class="border-2 border-black" />

            <!-- Modules Section / Sidebar -->
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

            <!-- Divider -->
            <hr class="border-2 border-black" />

            <!-- User Section -->
            <div class="space-y-3">
                {{-- Landing Button (Mobile Only) --}}
                <a href="{{ url('/') }}"
                    class="sm:hidden hover:cursor-pointer w-full rounded-lg px-3 py-2 text-sm font-semibold text-gray-800 bg-[#FFF7F1] hover:bg-gray-200 hover:shadow-md hover:scale-[1.02] focus:ring-2 focus:ring-[#FFF7F1] transition-all duration-200 cursor-pointer text-center block">
                    Go to Landing
                </a>
                @if (auth()->user()->user_type === 0)
                    <a href="{{ route('user.report') }}"
                        class="hover:cursor-pointer w-full rounded-lg px-3 py-2 text-sm font-semibold text-gray-800 bg-[#FFF7F1] hover:bg-gray-200 hover:shadow-md hover:scale-[1.02] focus:ring-2 focus:ring-[#FFF7F1] transition-all duration-200 cursor-pointer text-center block">
                        Report
                    </a>
                @endif
                <a href="{{ route('password.change') }}"
                    class="hover:cursor-pointer w-full rounded-lg px-3 py-2 text-sm font-semibold text-gray-800 bg-[#FFF7F1] hover:bg-gray-200 hover:shadow-md hover:scale-[1.02] focus:ring-2 focus:ring-[#FFF7F1] transition-all duration-200 cursor-pointer text-center block">
                    Change Password
                </a>

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <x-buttons.submit-button type="submit" color="white"
                        class="hover:cursor-pointer w-full rounded-full px-3 py-2 text-sm font-semibold text-gray-800 bg-[#FFF7F1] hover:bg-gray-200 hover:shadow-md hover:scale-[1.02] focus:ring-2 focus:ring-[#FFF7F1] transition-all duration-200 cursor-pointer text-center block">
                        Logout
                    </x-buttons.submit-button>
                </form>
            </div>
        </div>
    </div>
</nav>
