<a href="{{ $type === 'incoming' ? route('user.incoming-trucks') : route('user.outgoing-trucks') }}"
    class="flex-1 flex flex-wrap items-center border border-orange-300 rounded-lg p-6 shadow-sm hover:shadow-md transition"
    wire:poll>

    <div class="flex items-center space-x-3 min-w-[150px] text-gray-700 shrink-0">
        @if ($type === 'incoming')
            <img src="https://cdn-icons-png.flaticon.com/512/8591/8591505.png" alt="Incoming"
                class="h-7 w-7 object-contain"
                style="filter: brightness(0) saturate(100%) invert(48%) sepia(79%) saturate(2476%) hue-rotate(86deg) brightness(118%) contrast(119%);" />
        @else
            <img src="https://cdn-icons-png.flaticon.com/512/7468/7468319.png" alt="Outgoing"
                class="h-7 w-7 object-contain"
                style="filter: brightness(0) saturate(100%) invert(27%) sepia(51%) saturate(2878%) hue-rotate(346deg) brightness(104%) contrast(97%);" />
        @endif

        <span class="text-black font-semibold text-lg whitespace-nowrap">
            {{ $type === 'incoming' ? 'Incoming Trucks Today' : 'Outgoing Trucks Today' }}
        </span>
    </div>

    <span class="font-bold text-2xl ml-auto min-w-[50px] mt-2 md:mt-0">
        {{ str_pad($this->count, 4, '0', STR_PAD_LEFT) }}
    </span>

</a>
