<div wire:poll class="w-full grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
    @forelse ($locations as $location)
        <a href="{{ route('location.login', $location->id) }}"
            class="group relative overflow-hidden bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 border-2 border-gray-100 hover:border-orange-300"
            wire:key="location-{{ $location->id }}">

            <!-- Card Content -->
            <div class="p-5 sm:p-6">

                <!-- Logo Section -->
                <div class="flex justify-center items-center mb-4 sm:mb-5 h-20 sm:h-24">
                    @if ($location->attachment_id && $location->attachment)
                        <img src="{{ asset('storage/' . $location->attachment->path) }}"
                            alt="{{ $location->location_name }}"
                            class="max-h-full w-auto object-contain filter group-hover:scale-110 transition-transform duration-300">
                    @else
                        <div
                            class="flex items-center justify-center h-full w-full bg-linear-to-br from-orange-50 to-orange-100 rounded-xl px-4">
                            <span
                                class="text-lg sm:text-xl lg:text-2xl font-bold text-orange-700 text-center leading-tight">
                                {{ $location->location_name }}
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Location Name (always show below logo) -->
                <div class="text-center mb-3 sm:mb-4">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800 line-clamp-2">
                        {{ $location->location_name }}
                    </h3>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-200 mb-3 sm:mb-4"></div>

                <!-- Stats Section -->
                <div
                    class="flex items-center justify-between bg-linear-to-r from-red-50 to-orange-50 rounded-xl p-3 sm:p-4">
                    <div class="flex flex-col">
                        <span class="text-xs sm:text-sm font-medium text-gray-600 mb-1">Incoming Trucks</span>
                        <span class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-red-600">
                            {{ str_pad($location->ongoing_count, 2, '0', STR_PAD_LEFT) }}
                        </span>
                    </div>
                    <div
                        class="flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 bg-white rounded-full shadow-md group-hover:bg-orange-500 transition-colors">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-orange-500 group-hover:text-white transition-colors"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>

            </div>

            <!-- Bottom accent bar -->
            <div
                class="absolute bottom-0 left-0 right-0 h-1 bg-linear-to-r from-orange-400 to-red-500 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left">
            </div>

            <!-- Hover glow effect -->
            <div
                class="absolute inset-0 bg-linear-to-br from-orange-400/0 to-red-400/0 group-hover:from-orange-400/5 group-hover:to-red-400/5 transition-all duration-300 pointer-events-none rounded-2xl">
            </div>

        </a>
    @empty
        <div class="col-span-full">
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-8 sm:p-12 text-center">
                <svg class="w-16 h-16 sm:w-20 sm:h-20 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <p class="text-lg sm:text-xl font-semibold text-gray-700 mb-2">No Locations Available</p>
                <p class="text-sm sm:text-base text-gray-500">Please check back later or contact support.</p>
            </div>
        </div>
    @endforelse
</div>
