<div>
    <div class="p-4 sm:p-6 lg:p-8 bg-linear-to-br from-gray-50 to-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Main Grid Layout: Left Column (Stats) + Right Column (Quick Actions) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Left Column: Statistics (Takes 2 columns on desktop) -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Disinfection Statistics Section -->
                    <div class="bg-white rounded-3xl shadow-lg border border-gray-200 p-6 lg:p-8">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="p-3 bg-blue-100 rounded-xl">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-gray-800">Disinfection Statistics</h2>
                                    <p class="text-sm text-gray-500">Vehicle disinfection performance metrics</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Last updated</p>
                                <p class="text-sm font-semibold text-gray-700">{{ now()->format('M d, Y h:i A') }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Week to Date -->
                            <div wire:poll class="group">
                                <div
                                    class="p-4 bg-linear-to-br from-blue-50 to-blue-100/50 rounded-2xl border-2 border-blue-200 hover:border-blue-400 transition-all duration-300 hover:shadow-md">
                                    <div class="flex items-center gap-3">
                                        <div class="shrink-0 p-3 bg-blue-200 rounded-xl">
                                            <svg class="h-6 w-6 text-blue-700" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p
                                                class="text-xs font-semibold text-blue-700 uppercase tracking-wide mb-0.5">
                                                Week to Date</p>
                                            <p class="text-3xl font-bold text-gray-900">
                                                {{ number_format($this->stats['week_disinfected']) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Month to Date -->
                            <div wire:poll class="group">
                                <div
                                    class="p-4 bg-linear-to-br from-purple-50 to-purple-100/50 rounded-2xl border-2 border-purple-200 hover:border-purple-400 transition-all duration-300 hover:shadow-md">
                                    <div class="flex items-center gap-3">
                                        <div class="shrink-0 p-3 bg-purple-200 rounded-xl">
                                            <svg class="h-6 w-6 text-purple-700" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p
                                                class="text-xs font-semibold text-purple-700 uppercase tracking-wide mb-0.5">
                                                Month to Date</p>
                                            <p class="text-3xl font-bold text-gray-900">
                                                {{ number_format($this->stats['month_disinfected']) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Year to Date -->
                            <div wire:poll class="group">
                                <div
                                    class="p-4 bg-linear-to-br from-green-50 to-green-100/50 rounded-2xl border-2 border-green-200 hover:border-green-400 transition-all duration-300 hover:shadow-md">
                                    <div class="flex items-center gap-3">
                                        <div class="shrink-0 p-3 bg-green-200 rounded-xl">
                                            <svg class="h-6 w-6 text-green-700" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p
                                                class="text-xs font-semibold text-green-700 uppercase tracking-wide mb-0.5">
                                                Year to Date</p>
                                            <p class="text-3xl font-bold text-gray-900">
                                                {{ number_format($this->stats['year_disinfected']) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- All Time Total -->
                            <div wire:poll class="group">
                                <div
                                    class="p-4 bg-linear-to-br from-orange-500 to-yellow-500 rounded-2xl border-2 border-orange-400 hover:border-orange-600 transition-all duration-300 hover:shadow-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="shrink-0 p-3 bg-white/30 backdrop-blur-sm rounded-xl">
                                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p
                                                class="text-xs font-semibold text-white/90 uppercase tracking-wide mb-0.5">
                                                Total Disinfected</p>
                                            <p class="text-3xl font-bold text-white">
                                                {{ number_format($this->stats['total_disinfected']) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Resources Section -->
                    <div class="bg-white rounded-3xl shadow-lg border border-gray-200 p-6 lg:p-8">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="p-3 bg-indigo-100 rounded-xl">
                                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-gray-800">System Resources</h2>
                                    <p class="text-sm text-gray-500">Manage your system data and resources</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Guards -->
                            <a href="{{ route('admin.guards') }}" wire:poll class="group">
                                <div
                                    class="p-4 bg-linear-to-br from-cyan-50 to-cyan-100/50 rounded-2xl border-2 border-cyan-200 hover:border-cyan-400 transition-all duration-300 hover:shadow-md cursor-pointer">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="shrink-0 p-3 bg-cyan-200 rounded-xl group-hover:bg-cyan-300 transition-colors">
                                            <svg class="h-6 w-6 text-cyan-700" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p
                                                class="text-xs font-semibold text-cyan-700 uppercase tracking-wide mb-0.5">
                                                Guards</p>
                                            <p class="text-3xl font-bold text-gray-900">
                                                {{ number_format($this->stats['total_guards']) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </a>

                            <!-- Drivers -->
                            <a href="{{ route('admin.drivers') }}" wire:poll class="group">
                                <div
                                    class="p-4 bg-linear-to-br from-pink-50 to-pink-100/50 rounded-2xl border-2 border-pink-200 hover:border-pink-400 transition-all duration-300 hover:shadow-md cursor-pointer">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="shrink-0 p-3 bg-pink-200 rounded-xl group-hover:bg-pink-300 transition-colors">
                                            <svg class="h-6 w-6 text-pink-700" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p
                                                class="text-xs font-semibold text-pink-700 uppercase tracking-wide mb-0.5">
                                                Drivers</p>
                                            <p class="text-3xl font-bold text-gray-900">
                                                {{ number_format($this->stats['total_drivers']) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </a>

                            <!-- Plate Numbers -->
                            <a href="{{ route('admin.plate-numbers') }}" wire:poll class="group">
                                <div
                                    class="p-4 bg-linear-to-br from-amber-50 to-amber-100/50 rounded-2xl border-2 border-amber-200 hover:border-amber-400 transition-all duration-300 hover:shadow-md cursor-pointer">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="shrink-0 p-3 bg-amber-200 rounded-xl group-hover:bg-amber-300 transition-colors">
                                            <svg class="h-6 w-6 text-amber-700" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p
                                                class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-0.5">
                                                Plate Numbers</p>
                                            <p class="text-3xl font-bold text-gray-900">
                                                {{ number_format($this->stats['total_plate_numbers']) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </a>

                            <!-- Locations -->
                            <a href="{{ route('admin.locations') }}" wire:poll class="group">
                                <div
                                    class="p-4 bg-linear-to-br from-teal-50 to-teal-100/50 rounded-2xl border-2 border-teal-200 hover:border-teal-400 transition-all duration-300 hover:shadow-md cursor-pointer">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="shrink-0 p-3 bg-teal-200 rounded-xl group-hover:bg-teal-300 transition-colors">
                                            <svg class="h-6 w-6 text-teal-700" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p
                                                class="text-xs font-semibold text-teal-700 uppercase tracking-wide mb-0.5">
                                                Locations</p>
                                            <p class="text-3xl font-bold text-gray-900">
                                                {{ number_format($this->stats['total_locations']) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </a>

                            <!-- Reports -->
                            <a href="{{ route('admin.reports') }}" wire:poll class="group">
                                <div
                                    class="p-4 bg-linear-to-br from-red-50 to-red-100/50 rounded-2xl border-2 border-red-200 hover:border-red-400 transition-all duration-300 hover:shadow-md cursor-pointer">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="shrink-0 p-3 bg-red-200 rounded-xl group-hover:bg-red-300 transition-colors">
                                            <svg class="h-6 w-6 text-red-700" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p
                                                class="text-xs font-semibold text-red-700 uppercase tracking-wide mb-0.5">
                                                Unresolved Reports</p>
                                            <p class="text-3xl font-bold text-gray-900">
                                                {{ number_format($this->stats['unresolved_reports']) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                </div>

                <!-- Right Column: Quick Actions (Takes 1 column on desktop) -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-3xl shadow-lg border border-gray-200 p-6 lg:p-8 lg:sticky lg:top-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-gray-100 rounded-xl">
                                <svg class="h-6 w-6 text-gray-700" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">Quick Actions</h2>
                                <p class="text-sm text-gray-500">Shortcuts</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <a href="{{ route('admin.guards') }}"
                                class="group block p-4 bg-linear-to-br from-blue-50 to-blue-100/50 rounded-2xl border-2 border-blue-200 hover:border-blue-400 hover:shadow-md hover:cursor-pointer transition-all duration-300">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="p-2.5 bg-blue-200 rounded-xl group-hover:bg-blue-300 transition-colors">
                                        <svg class="h-5 w-5 text-blue-700" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    </div>
                                    <span
                                        class="font-semibold text-gray-800 group-hover:text-blue-700 transition-colors">Manage
                                        Guards</span>
                                </div>
                            </a>

                            <a href="{{ route('admin.drivers') }}"
                                class="group block p-4 bg-linear-to-br from-pink-50 to-pink-100/50 rounded-2xl border-2 border-pink-200 hover:border-pink-400 hover:shadow-md hover:cursor-pointer transition-all duration-300">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="p-2.5 bg-pink-200 rounded-xl group-hover:bg-pink-300 transition-colors">
                                        <svg class="h-5 w-5 text-pink-700" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <span
                                        class="font-semibold text-gray-800 group-hover:text-pink-700 transition-colors">Manage
                                        Drivers</span>
                                </div>
                            </a>

                            <a href="{{ route('admin.plate-numbers') }}"
                                class="group block p-4 bg-linear-to-br from-amber-50 to-amber-100/50 rounded-2xl border-2 border-amber-200 hover:border-amber-400 hover:shadow-md hover:cursor-pointer transition-all duration-300">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="p-2.5 bg-amber-200 rounded-xl group-hover:bg-amber-300 transition-colors">
                                        <svg class="h-5 w-5 text-amber-700" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                    </div>
                                    <span
                                        class="font-semibold text-gray-800 group-hover:text-amber-700 transition-colors">Manage
                                        Plate Numbers</span>
                                </div>
                            </a>

                            <a href="{{ route('admin.locations') }}"
                                class="group block p-4 bg-linear-to-br from-green-50 to-green-100/50 rounded-2xl border-2 border-green-200 hover:border-green-400 hover:shadow-md hover:cursor-pointer transition-all duration-300">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="p-2.5 bg-green-200 rounded-xl group-hover:bg-green-300 transition-colors">
                                        <svg class="h-5 w-5 text-green-700" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <span
                                        class="font-semibold text-gray-800 group-hover:text-green-700 transition-colors">Manage
                                        Locations</span>
                                </div>
                            </a>

                            <a href="{{ route('admin.trucks') }}"
                                class="group block p-4 bg-linear-to-br from-purple-50 to-purple-100/50 rounded-2xl border-2 border-purple-200 hover:border-purple-400 hover:shadow-md hover:cursor-pointer transition-all duration-300">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="p-2.5 bg-purple-200 rounded-xl group-hover:bg-purple-300 transition-colors">
                                        <svg class="h-5 w-5 text-purple-700" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <span
                                        class="font-semibold text-gray-800 group-hover:text-purple-700 transition-colors">View
                                        Disinfection Slips</span>
                                </div>
                            </a>

                            <a href="{{ route('admin.reports') }}"
                                class="group block p-4 bg-linear-to-br from-red-50 to-red-100/50 rounded-2xl border-2 border-red-200 hover:border-red-400 hover:shadow-md hover:cursor-pointer transition-all duration-300">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="p-2.5 bg-red-200 rounded-xl group-hover:bg-red-300 transition-colors">
                                        <svg class="h-5 w-5 text-red-700" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                                        </svg>
                                    </div>
                                    <span
                                        class="font-semibold text-gray-800 group-hover:text-red-700 transition-colors">View
                                        Reports</span>
                                </div>
                            </a>

                            <a href="{{ route('admin.audit-trail') }}"
                                class="group block p-4 bg-linear-to-br from-gray-50 to-gray-100/50 rounded-2xl border-2 border-gray-200 hover:border-gray-400 hover:shadow-md hover:cursor-pointer transition-all duration-300">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="p-2.5 bg-gray-200 rounded-xl group-hover:bg-gray-300 transition-colors">
                                        <svg class="h-5 w-5 text-gray-700" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <span
                                        class="font-semibold text-gray-800 group-hover:text-gray-700 transition-colors">View
                                        Audit Trail</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>
