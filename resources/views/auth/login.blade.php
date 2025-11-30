<x-layout>
    <!-- Header -->
    <header class="bg-[#FFDBBD] rounded-md shadow-xl p-4 fixed top-0 left-0 w-full z-10">
        <div class="flex justify-between items-center">
            <!-- Logo + Header Text -->
            <div class="flex items-center space-x-3">
                <img src="{{ asset('image/logo/BGC.png') }}" alt="Logo" class="h-10 w-auto">
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
                    Log in to your account
                </h2>
            </div>

            <form method="POST" action="/login" class="space-y-6">
                @csrf

                <div>
                    <x-input-form name="username" required :value="old('username')" placeholder="Username">Username</x-input-form>
                    <x-error name="username" />
                </div>

                <div>
                    <x-input-form type="password" name="password" required placeholder="Password">Password</x-input-form>
                    <x-error name="password" />
                </div>

                <div>
                    <x-submit-button type="submit" class="mt-2 w-full">Login</x-submit-button>

                    <!-- Back link below the login button with underline on hover -->
                    <div class="mt-4 text-center">
                        <a href="/" class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:underline">
                            Back
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layout>
