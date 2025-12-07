<x-layout>
    <!-- Header -->
    <header
        class="bg-linear-to-r from-orange-100 via-orange-50 to-orange-100 shadow-sm p-4 sm:p-6 fixed top-0 left-0 w-full z-20">
        <div class="flex justify-between items-center">
            <!-- Logo + Header Text -->
            <a href="{{ url('/') }}" class="flex items-center space-x-3 hover:cursor-pointer">
                <img src="{{ asset('storage/images/logo/BGC.png') }}" alt="Logo" class="h-10 w-auto">
                <div class="font-bold text-gray-800 text-[clamp(1rem,1.8vw,1.5rem)] leading-none">
                    Digital Disinfection Slip System
                </div>
            </a>

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
            <!-- Flash Message -->
            @if (session('status'))
                <div
                    class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg shadow-sm flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span>{{ session('status') }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="ml-4 text-sm font-medium text-blue-700 hover:text-blue-900 underline hover:cursor-pointer cursor-pointer">
                            Logout
                        </button>
                    </form>
                </div>
            @endif

            <!-- Location Cards -->
            <livewire:trucks.location-cards />

        </div>
    </main>
</x-layout>
