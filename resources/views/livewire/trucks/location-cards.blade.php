<div wire:poll class="w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
    @forelse ($locations as $location)
        <a href="{{ route('location.login', $location->id) }}"
            class="bg-white rounded-xl shadow-xl p-6 flex items-center gap-6 min-h-[140px] hover:shadow-2xl transition-shadow cursor-pointer"
            wire:key="location-{{ $location->id }}">
            <!-- Logo or Name -->
            <div class="shrink-0 flex justify-center items-center">
                @if ($location->attachment_id && $location->attachment)
                    <img src="{{ asset('storage/' . $location->attachment->path) }}" alt="{{ $location->location_name }}"
                        class="h-24 w-auto object-contain">
                @else
                    <span class="text-xl font-bold text-gray-700">{{ $location->location_name }}</span>
                @endif
            </div>

            <!-- Number + Label -->
            <div class="flex flex-col justify-center items-start">
                <span class="text-4xl font-extrabold text-red-600">{{ $location->ongoing_count }}</span>
                <span class="text-lg font-semibold text-gray-700 mt-1">Incoming Trucks</span>
            </div>
        </a>
    @empty
        <div class="col-span-full text-center text-gray-500 py-8">
            No locations available.
        </div>
    @endforelse
</div>
