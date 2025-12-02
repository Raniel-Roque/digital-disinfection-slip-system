@props([
    'module' => 'Dashboard',
])

<nav x-data="{ open: false }" class="bg-[#FFDBBD] shadow-md rounded-md px-4 py-3 flex items-center justify-between">
    <!-- Left: Hamburger + Module Name + Date -->
    <div class="flex items-center gap-3">
        <button type="button"
            class="hover:cursor-pointer shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-lg bg-white border-2 border-gray-300 text-gray-700 shadow-sm hover:bg-[#EC8B18] hover:border-[#EC8B18] hover:text-white hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#EC8B18] focus:ring-offset-2 transition-all duration-200 cursor-pointer"
            x-on:click="open = true" aria-label="Open menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <div class="flex flex-col transition-all duration-200" :class="open ? 'lg:ml-60' : ''">
            <span class="font-semibold text-gray-800 text-lg">{{ $module }}</span>
            <span class="text-sm text-gray-600">{{ now()->format('F d, Y') }}</span>
        </div>
    </div>

    <!-- Right: Slot for actions -->
    <div class="flex items-center gap-3">
        {{ $slot }}
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
                        {{ auth()->user()->first_name }} {{ strtoupper(substr(auth()->user()->middle_name, 0, 1)) }}.
                        {{ auth()->user()->last_name }}
                    </span>
                    <span
                        class="text-sm text-gray-500 wrap-break-word mt-0.5">{{ '@' . (auth()->user()->username ?? 'username') }}</span>
                </div>
                <button type="button"
                    class="hover:cursor-pointer shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-lg bg-white border-2 border-gray-300 text-gray-700 shadow-sm hover:bg-[#EC8B18] hover:border-[#EC8B18] hover:text-white hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#EC8B18] focus:ring-offset-2 transition-all duration-200 cursor-pointer"
                    x-on:click="open = false" aria-label="Close menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            </div>

            <!-- Divider -->
            <hr class="border-2 border-black" />

            <!-- Modules Section / Sidebar -->
            <div class="space-y-1">
                @switch(auth()->user()->user_type)
                    @case(0)
                        <livewire:sidebar-user :currentRoute="Route::currentRouteName()" />
                    @break

                    @case(1)
                        <livewire:sidebar-admin />
                    @break

                    @case(2)
                        <livewire:sidebar-super-admin />
                    @break
                @endswitch
            </div>

            <!-- Divider -->
            <hr class="border-2 border-black" />

            <!-- User Section -->
            <div class="space-y-3">
                <a href="{{ route('password.change') }}"
                    class="hover:cursor-pointer w-full rounded-full px-3 py-2 text-sm font-semibold text-gray-800 bg-[#FFF7F1] hover:bg-gray-200 hover:shadow-md hover:scale-[1.02] focus:ring-2 focus:ring-[#FFF7F1] transition-all duration-200 cursor-pointer text-center block">
                    Change Password
                </a>

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <x-submit-button type="submit" color="white"
                        class="hover:cursor-pointer w-full rounded-full px-3 py-2 text-sm font-semibold text-gray-800 bg-[#FFF7F1] hover:bg-gray-200 hover:shadow-md hover:scale-[1.02] focus:ring-2 focus:ring-[#FFF7F1] transition-all duration-200 cursor-pointer text-center block">
                        Logout
                    </x-submit-button>
                </form>
            </div>
        </div>
    </div>
</nav>
