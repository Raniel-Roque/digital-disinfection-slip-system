<x-layout>
    <!-- Header -->
    <header class="bg-[#FFDBBD] rounded-md shadow-xl p-4 fixed top-0 left-0 w-full z-10">
        <div class="flex justify-between items-center">
            <!-- Logo + Header Text -->
            <div class="flex items-center space-x-3">
                <img src="{{ asset('storage\images\logo\BGC.png') }}" alt="Logo" class="h-10 w-auto">
                <div class="font-bold text-gray-800 text-[clamp(1rem,1.8vw,1.5rem)] leading-none">
                    Digital Disinfection Slip System
                </div>
            </div>
        </div>
    </header>

    <!-- Content below header, full height minus header height -->
    <div class="flex items-center justify-center px-4 min-h-screen pt-12">
        <div class="w-full max-w-md md:max-w-lg rounded-xl bg-white shadow-lg ring-1 ring-gray-300 p-10">

            <div class="flex flex-col items-center mb-6">
                <img src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=600" class="h-10">
                <h2 class="mt-6 text-2xl font-semibold text-gray-900 text-center">
                    @if (isset($location) && $location)
                        Log in to your account
                    @else
                        Log in to your account as admin
                    @endif
                </h2>
            </div>

            @if (isset($location) && $location)
                <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex items-center gap-3">
                        @if ($location->attachment_id && $location->attachment)
                            <img src="{{ asset('storage/' . $location->attachment->path) }}"
                                alt="{{ $location->location_name }}" class="h-12 w-auto object-contain">
                        @endif
                        <div>
                            <p class="text-sm text-gray-600 font-medium">Location</p>
                            <p class="text-lg font-semibold text-gray-900">{{ $location->location_name }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST"
                action="{{ isset($location) && $location ? route('location.login.store', $location->id) : route('login.store') }}"
                class="space-y-6">
                @csrf

                <div>
                    <x-forms.input-form name="username" required :value="old('username')"
                        placeholder="Username">Username</x-forms.input-form>
                    <x-forms.error name="username" />
                </div>

                <div>
                    <x-forms.input-form type="password" name="password" required
                        placeholder="Password">Password</x-forms.input-form>
                    <x-forms.error name="password" />
                </div>

                <div>
                    <x-buttons.submit-button type="submit" class="mt-2 w-full">Login</x-buttons.submit-button>

                    <!-- Back link below the login button with underline on hover -->
                    <div class="mt-4 text-center">
                        <a href="/"
                            class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:underline">
                            Back
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layout>
