<x-layout>
    <!-- Header -->
    <header
        class="bg-linear-to-r from-orange-100 via-orange-50 to-orange-100 shadow-lg p-4 sm:p-6 fixed top-0 left-0 w-full z-20">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <!-- Logo + Header Text -->
            <div class="flex items-center space-x-3 sm:space-x-4">
                <img src="{{ asset('storage/images/logo/BGC.png') }}" alt="Logo" class="h-10 sm:h-12 w-auto">
                <div class="font-bold text-gray-800 text-sm sm:text-base md:text-lg lg:text-xl leading-tight">
                    Digital Disinfection<br class="sm:hidden"> Slip System
                </div>
            </div>

            <!-- Login / Dashboard Button -->
            <div class="whitespace-nowrap">
                @guest
                    <x-buttons.nav-button href="/login">
                        <span class="hidden sm:inline">Login as admin</span>
                        <span class="sm:hidden">Login</span>
                    </x-buttons.nav-button>
                @endguest

                @auth
                    <x-buttons.nav-button href="{{ route(auth()->user()->dashboardRoute()) }}">
                        Dashboard
                    </x-buttons.nav-button>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 px-4 sm:px-6 lg:px-8 pt-24 sm:pt-28 pb-8 bg-linear-to-br from-gray-50 to-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto">
            <!-- Location Cards -->
            <livewire:trucks.location-cards />

        </div>
    </main>
</x-layout>
