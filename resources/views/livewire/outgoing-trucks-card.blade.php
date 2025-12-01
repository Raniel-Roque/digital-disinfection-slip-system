<a href="{{ route('user.outgoing-trucks') }}" class="flex-1 flex flex-wrap items-center border border-green-300 rounded-lg p-6 shadow-sm hover:shadow-md transition" wire:poll="updateCount">
    <div class="flex items-center space-x-3 min-w-[150px] text-gray-700 shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 014-4h3" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13l3 3 4-4" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7v5" />
        </svg>
        <span class="font-semibold text-lg whitespace-nowrap">Outgoing Trucks Today</span>
    </div>
    <span class="text-green-600 font-bold text-2xl ml-auto min-w-[50px] mt-2 md:mt-0">{{ str_pad($count, 4, '0', STR_PAD_LEFT) }}</span>
</a>

