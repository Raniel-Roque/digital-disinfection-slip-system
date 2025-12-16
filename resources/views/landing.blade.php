<x-layout>
    <!-- Navbar -->
    <nav class="bg-[#ffb97f] shadow-md rounded-md px-2 sm:px-4 py-2 sm:py-3 fixed top-0 left-0 w-full z-20">
        <!-- Mobile: Simple Layout - Logo + Title + Login Button -->
        <div class="flex items-center justify-between gap-3 sm:hidden">
            <a href="{{ auth()->check() ? route(auth()->user()->dashboardRoute()) : url('/') }}" class="flex items-center gap-2.5 flex-1 justify-center min-w-0 hover:opacity-80 transition-opacity">
                <img src="{{ asset('storage/images/logo/BGC.png') }}" alt="Logo" class="h-10 w-10 object-contain shrink-0">
                <span class="font-bold text-gray-800 text-base truncate">Digital Disinfection Slip System</span>
            </a>
            <!-- Login Button (Mobile) -->
            <div class="shrink-0">
                @guest
                    <x-buttons.nav-button href="/login">
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

        <!-- Desktop: Horizontal Layout -->
        <div class="hidden sm:flex items-center justify-between gap-3">
            <!-- Left: Logo + Title + Date -->
            <a href="{{ auth()->check() ? route(auth()->user()->dashboardRoute()) : url('/') }}" class="flex items-center gap-3 min-w-0 hover:opacity-80 transition-opacity">
                <img src="{{ asset('storage/images/logo/BGC.png') }}" alt="Logo" class="h-12 w-12 object-contain shrink-0">
                <!-- Title + Date (stacked) -->
                <div class="flex flex-col">
                    <span class="font-semibold text-gray-800 text-lg truncate">Digital Disinfection Slip System</span>
                    <span class="text-sm text-gray-600">{{ now()->format('F d, Y') }}</span>
                </div>
            </a>

            <!-- Right: Login / Dashboard Button -->
            <div class="flex items-center gap-3 shrink-0 ml-auto">
                @guest
                    <x-buttons.nav-button href="/login">
                        <span class="hidden sm:inline">Login as admin</span>
                    </x-buttons.nav-button>
                @endguest
                @auth
                    <x-buttons.nav-button href="{{ route(auth()->user()->dashboardRoute()) }}">
                        Dashboard
                    </x-buttons.nav-button>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 px-4 sm:px-6 lg:px-8 pt-20 sm:pt-24 pb-8 bg-linear-to-br from-gray-50 to-gray-100 min-h-screen">
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
                    <div x-data="{ showLogoutConfirm: false, isLoggingOut: false }">
                        <button type="button" @click="showLogoutConfirm = true" :disabled="isLoggingOut"
                            class="ml-4 text-sm font-medium text-blue-700 hover:text-blue-900 underline hover:cursor-pointer cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            Logout
                        </button>
                        
                        <!-- Confirmation Dialog -->
                        <div x-show="showLogoutConfirm" x-cloak
                            class="fixed inset-0 z-100 flex items-center justify-center bg-black bg-opacity-50"
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
                    </div>
                </div>
            @endif

            <!-- Location Cards -->
            <livewire:trucks.location-cards />

        </div>
    </main>
</x-layout>
