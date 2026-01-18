<x-layout>
    <!-- Navbar -->
    <nav class="bg-[#ffb97f] shadow-md rounded-md px-2 sm:px-4 py-2 sm:py-3 fixed top-0 left-0 w-full z-20">
        <!-- Mobile: Simple Layout - Logo + Title -->
        <div class="flex items-center justify-between gap-3 sm:hidden">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 min-w-0 hover:opacity-80 transition-opacity">
                <img src="{{ asset('storage/' . $defaultLogoPath) }}" alt="Logo" class="h-15 w-30 object-contain shrink-0">
                <div class="font-bold text-gray-800 text-base leading-tight">
                    <div>Digital Disinfection</div>
                    <div>Slip System</div>
                </div>
            </a>
        </div>

        <!-- Desktop: Horizontal Layout -->
        <div class="hidden sm:flex items-center justify-between gap-3">
            <!-- Left: Logo + Title + Date -->
            <a href="{{ url('/') }}" class="flex items-center gap-3 min-w-0 hover:opacity-80 transition-opacity">
                <img src="{{ asset('storage/' . $defaultLogoPath) }}" alt="Logo" class="h-15 w-30 object-contain shrink-0">
                <!-- Title + Date (stacked) -->
                <div class="flex flex-col">
                    <span class="font-semibold text-gray-800 text-lg truncate">Digital Disinfection Slip System</span>
                    <span class="text-sm text-gray-600">{{ now()->format('F d, Y') }}</span>
                </div>
            </a>
        </div>
    </nav>

    <!-- Content below navbar, full height minus navbar height -->
    <div class="flex items-center justify-center px-4 min-h-screen pt-20 sm:pt-24 bg-linear-to-br from-gray-50 to-gray-100">
        <div class="w-full max-w-md md:max-w-lg rounded-xl bg-white shadow-lg ring-1 ring-gray-300 p-10">
            <div class="flex flex-col items-center mb-4">
                <img src="{{ asset('storage/' . $defaultLogoPath) }}" class="h-15 w-auto">
                <h2 class="mt-1 text-2xl font-semibold text-gray-900 text-center">
                    @if (isset($location) && $location)
                        Log in to your account
                    @else
                        Log in as Admin
                    @endif
                </h2>
            </div>

            @if (isset($location) && $location)
                <div class="mb-6 p-3 bg-orange-50 rounded-lg border border-orange-200">
                    <div class="flex items-center gap-2.5">
                        @if ($location->photo_id && $location->Photo)
                            <img src="{{ asset('storage/' . $location->Photo->file_path) }}"
                                alt="{{ $location->location_name }}" class="h-10 w-auto object-contain">
                        @else
                            <img src="{{ asset('storage/' . $defaultLogoPath) }}"
                                alt="{{ $location->location_name }}" class="h-10 w-auto object-contain">
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-orange-600 font-semibold uppercase tracking-wide">Location</p>
                            <p class="text-base font-bold text-gray-900 truncate">{{ $location->location_name }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST"
                action="{{ isset($location) && $location ? route('location.login.store', $location->id) : route('login.store') }}"
                class="space-y-6" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf

                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <input type="text" name="username" id="username" required value="{{ old('username') }}"
                            placeholder="Username" :readonly="submitting"
                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors text-sm read-only:opacity-50 read-only:cursor-not-allowed">
                    </div>
                    <x-forms.error name="username" />
                </div>

                <div x-data="{ showPassword: false }">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input :type="showPassword ? 'text' : 'password'" name="password" id="password" required
                            placeholder="Password" :readonly="submitting"
                            class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors text-sm read-only:opacity-50 read-only:cursor-not-allowed">
                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg x-show="!showPassword" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="showPassword" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                    <x-forms.error name="password" />
                </div>

                <div>
                    <x-buttons.submit-button type="submit" color="orange" :fullWidth="true" x-bind:disabled="submitting" class="mt-2">
                        <svg x-show="submitting" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="submitting ? 'Logging in...' : 'Login'"></span>
                    </x-buttons.submit-button>

                    <!-- Back link below the login button with underline on hover -->
                    <div class="mt-4 text-center">
                        <a href="/"
                            class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:underline hover:cursor-pointer cursor-pointer">
                            Back
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

</x-layout>
