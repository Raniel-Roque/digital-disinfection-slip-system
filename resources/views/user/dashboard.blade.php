<x-layout>
    <x-navigation.navbar module="Dashboard" />

    <!-- Slip Arrival Monitor -->
    <livewire:slip-arrival-monitor />

    <div class="p-4 sm:p-6 lg:p-8 bg-linear-to-br from-gray-50 to-gray-100 min-h-screen">

        <div class="max-w-7xl mx-auto">
            <!-- Stats Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 {{ ($canCreateSlip ?? false) ? 'lg:grid-cols-3' : '' }} gap-6 mb-8">

                <!-- Incoming Slips Card -->
                <a href="{{ route('user.incoming-slips') }}"
                    class="group relative overflow-hidden bg-white rounded-2xl shadow-md hover:shadow-xl hover:cursor-pointer transition-all duration-300 border border-gray-200 hover:border-green-400"
                    wire:poll>
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-3 bg-green-100 rounded-xl group-hover:bg-green-200 transition-colors">
                                    <img src="https://cdn-icons-png.flaticon.com/512/8591/8591505.png" alt="Incoming"
                                        class="h-8 w-8 object-contain"
                                        style="filter: brightness(0) saturate(100%) invert(48%) sepia(79%) saturate(2476%) hue-rotate(86deg) brightness(118%) contrast(119%);" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Incoming</h3>
                                    <p class="text-xs text-gray-400 mt-0.5">Slips Today</p>
                                </div>
                            </div>
                            <svg class="h-5 w-5 text-gray-400 group-hover:text-green-500 group-hover:translate-x-1 transition-all"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <div class="flex items-end justify-between">
                            <p class="text-4xl font-bold text-gray-800">
                                <livewire:slips.slip-count-card type="incoming" :key="'incoming-count'" />
                            </p>
                            <span
                                class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full font-medium">Active</span>
                        </div>
                    </div>
                    <div
                        class="absolute bottom-0 left-0 right-0 h-1 bg-linear-to-r from-green-400 to-green-600 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left">
                    </div>
                </a>

                <!-- Outgoing Slips Card -->
                <a href="{{ route('user.outgoing-slips') }}"
                    class="group relative overflow-hidden bg-white rounded-2xl shadow-md hover:shadow-xl hover:cursor-pointer transition-all duration-300 border border-gray-200 hover:border-red-400"
                    wire:poll>
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-3 bg-red-100 rounded-xl group-hover:bg-red-200 transition-colors">
                                    <img src="https://cdn-icons-png.flaticon.com/512/7468/7468319.png" alt="Outgoing"
                                        class="h-8 w-8 object-contain"
                                        style="filter: brightness(0) saturate(100%) invert(27%) sepia(51%) saturate(2878%) hue-rotate(346deg) brightness(104%) contrast(97%);" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Outgoing</h3>
                                    <p class="text-xs text-gray-400 mt-0.5">Slips Today</p>
                                </div>
                            </div>
                            <svg class="h-5 w-5 text-gray-400 group-hover:text-red-500 group-hover:translate-x-1 transition-all"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <div class="flex items-end justify-between">
                            <p class="text-4xl font-bold text-gray-800">
                                <livewire:slips.slip-count-card type="outgoing" :key="'outgoing-count'" />
                            </p>
                            <span
                                class="text-xs text-red-600 bg-red-50 px-2 py-1 rounded-full font-medium">Active</span>
                        </div>
                    </div>
                    <div
                        class="absolute bottom-0 left-0 right-0 h-1 bg-linear-to-r from-red-400 to-red-600 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left">
                    </div>
                </a>

                <!-- Create Slip Action Card (Only if location allows creating slips) -->
                @if ($canCreateSlip ?? false)
                    <a href="{{ route('user.outgoing-slips', ['openCreate' => true]) }}"
                        class="group relative overflow-hidden bg-linear-to-br from-blue-500 to-blue-600 rounded-2xl shadow-md hover:shadow-xl hover:cursor-pointer transition-all duration-300 border border-blue-400 hover:scale-105">
                        <div class="p-6 h-full flex flex-col justify-between">
                            <div class="flex items-start justify-between mb-4">
                                <div
                                    class="p-3 bg-white/20 backdrop-blur-sm rounded-xl group-hover:bg-white/30 transition-colors">
                                    <svg xmlns="https://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                <div
                                    class="h-8 w-8 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center group-hover:bg-white/20 transition-colors">
                                    <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white mb-2">Create New Slip</h3>
                                <p class="text-blue-100 text-sm">Create a new disinfection slip</p>
                            </div>
                            <div class="mt-4 flex items-center text-white text-sm font-medium">
                                <span class="mr-2">Quick Action</span>
                                <svg class="h-4 w-4 group-hover:translate-x-1 transition-transform" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </div>
                        </div>
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 bg-white/10 rounded-full blur-2xl"></div>
                        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 h-20 w-20 bg-white/10 rounded-full blur-xl"></div>
                    </a>
                @endif

            </div>

            <!-- Quick Stats Bar -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mb-8" wire:poll>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-800">
                                <livewire:slips.slip-count-card type="total" :key="'total-count'" />
                            </p>
                            <p class="text-xs text-gray-500">Total Slips Today</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-yellow-100 rounded-xl">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-800">
                                <livewire:slips.slip-count-card type="pending" :key="'pending-count'" />
                            </p>
                            <p class="text-xs text-gray-500">Pending</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-orange-100 rounded-xl">
                            <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-800">
                                <livewire:slips.slip-count-card type="inprogress" :key="'inprogress-count'" />
                            </p>
                            <p class="text-xs text-gray-500">Disinfecting</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-green-100 rounded-xl">
                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-800">
                                <livewire:slips.slip-count-card type="completed" :key="'completed-count'" />
                            </p>
                            <p class="text-xs text-gray-500">Completed Today</p>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>
</x-layout>
