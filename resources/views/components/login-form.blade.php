<div class="flex min-h-screen items-center justify-center px-4 py-12">
        <div
            class="w-full max-w-sm rounded-xl bg-white shadow-md ring-1 ring-gray-300 p-6">

            <div class="flex flex-col items-center mb-6">
                <img src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=600" alt="Logo"
                    class="h-10">

                <img src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=500" alt="Logo"
                    class="h-10 hidden">

                <h2 class="mt-6 text-xl font-semibold text-gray-900">
                    Log in to your account
                </h2>
            </div>

            <form method="POST" action="/" class="space-y-5">
                @csrf

                <x-login-input name="username" required :value="old('email')">Username</x-login-input>

                <x-login-input type="password" name="password" required>Password</x-login-input>

                <x-button type="submit">Login</x-button>
            </form>
        </div>
    </div>