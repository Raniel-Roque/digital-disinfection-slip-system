<x-layout>
    <!-- Header -->
    <header class="bg-[#FFDBBD] rounded-md shadow-xl p-4 fixed top-0 left-0 w-full z-20">
        <div class="flex justify-between items-center">
            <!-- Logo + Header Text -->
            <div class="flex items-center space-x-3">
                <img src="{{ asset('storage\images\logo\BGC.png') }}" alt="Logo" class="h-10 w-auto">
                <div
                    class="font-bold text-gray-800 text-[clamp(1rem,1.8vw,1.5rem)] leading-snug whitespace-normal wrap-break-word">
                    Digital Disinfection Slip System
                </div>
            </div>

            <!-- Login / Dashboard Button -->
            <div class="whitespace-nowrap">
                @guest
                    <x-buttons.nav-button href="/login">Login as admin</x-buttons.nav-button>
                @endguest

                @auth
                    <x-buttons.nav-button
                        href="{{ route(auth()->user()->dashboardRoute()) }}">Dashboard</x-buttons.nav-button>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Content Placeholder -->
    <main class="flex-1 p-6 pt-28 flex justify-center">
        <livewire:trucks.location-cards />
    </main>
</x-layout>
