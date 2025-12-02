<x-modal-template show="showFilters" title="Filter Options">

    {{-- Date Range Filter --}}
    <div x-data="{ fromDate: @entangle('filterDateFrom') }">
        <label @class(['block', 'text-sm', 'font-medium', 'text-gray-700', 'mb-2'])>Date Range</label>
        <div @class(['grid', 'grid-cols-2', 'gap-3'])>
            <div>
                <label @class(['block', 'text-xs', 'text-gray-500', 'mb-1'])>From</label>
                <input type="date" wire:model.live="filterDateFrom" x-model="fromDate" max="{{ date('Y-m-d') }}"
                    class="py-2 px-3 block w-full border-gray-300 shadow-sm rounded-lg text-sm 
                            focus:border-orange-500 focus:ring-orange-500">
            </div>
            <div>
                <label @class(['block', 'text-xs', 'text-gray-500', 'mb-1'])>To</label>
                <input type="date" wire:model.live="filterDateTo" :min="fromDate" max="{{ date('Y-m-d') }}"
                    class="py-2 px-3 block w-full border-gray-300 shadow-sm rounded-lg text-sm 
                            focus:border-orange-500 focus:ring-orange-500">
            </div>
        </div>
    </div>

    {{-- Status Filter --}}
    <div @class(['mt-4'])>
        <label @class(['block', 'text-sm', 'font-medium', 'text-gray-700', 'mb-1'])>Status</label>
        <select wire:model.live="filterStatus"
            class="cursor-pointer py-2 px-3 block w-full border-gray-300 shadow-sm rounded-lg text-sm 
                focus:border-orange-500 focus:ring-orange-500">
            <option value="">All</option>
            <option value="0">Ongoing</option>
            <option value="1">Disinfecting</option>
        </select>
    </div>

    {{-- Footer slot --}}
    <x-slot name="footer">
        <x-submit-button wire:click="clearFilters" color="white"
            class="px-4 py-2 text-sm font-medium text-black bg-white border border-gray-300 
                   rounded-lg hover:bg-gray-50 transition">
            Clear Filters
        </x-submit-button>
        <x-submit-button wire:click="applyFilters" @click="show = false"
            class="px-4 py-2 text-sm font-medium text-white bg-orange-500 
                   rounded-lg hover:bg-orange-600 transition">
            Apply
        </x-submit-button>
    </x-slot>

</x-modal-template>
