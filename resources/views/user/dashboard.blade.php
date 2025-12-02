<x-layout>
    <x-navbar module="Dashboard">
        <x-slot:sidebar>
            <livewire:sidebar-user :currentRoute="Route::currentRouteName()" />
        </x-slot:sidebar>
    </x-navbar>

    <div class="p-4 bg-white min-h-screen flex flex-col items-center">

        <div class="w-full max-w-7xl rounded-xl shadow-md p-4 bg-white">

            <div class="flex flex-col md:flex-row md:space-x-6 space-y-6 md:space-y-0">

                <!-- Incoming Trucks -->
                <livewire:truck-count-card type="incoming" />

                <!-- Outgoing Trucks -->
                <livewire:truck-count-card type="outgoing" />

                <!-- Create Disinfection Slip -->
                <a href="#"
                    class="flex-1 flex items-center space-x-4 border border-orange-300 rounded-lg p-6 shadow-sm hover:shadow-md transition text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-green-600" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="font-semibold text-lg">Create Disinfection Slip</span>
                </a>
            </div>
        </div>
    </div>
</x-layout>
