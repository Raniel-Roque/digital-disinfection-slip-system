{{-- Filter Modal Component --}}

<div 
    x-data="{ show: @entangle('showFilters') }"
    x-show="show"
    @close-modal.window="show = false"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    {{-- Backdrop --}}
    <div 
        class="fixed inset-0 bg-black/80 transition-opacity"
        @click="$wire.cancelFilters(); show = false"
    ></div>

    {{-- Modal Panel --}}
    <div class="flex min-h-full items-center justify-center p-4">
        <div 
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative bg-white rounded-xl shadow-xl w-full max-w-md"
            @click.stop
        >
            {{-- Header --}}
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Filter Options</h3>
                <button 
                    @click="$wire.cancelFilters(); show = false"
                    class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-4 space-y-4">
                
                {{-- Date Range Filter --}}
                <div x-data="{ fromDate: @entangle('filterDateFrom') }">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">From</label>
                            <input 
                                type="date"
                                wire:model.live="filterDateFrom"
                                x-model="fromDate"
                                max="{{ date('Y-m-d') }}"
                                class="py-2 px-3 block w-full border-gray-300 shadow-sm rounded-lg text-sm 
                                        focus:border-orange-500 focus:ring-orange-500"
                            >
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">To</label>
                            <input 
                                type="date"
                                wire:model.live="filterDateTo"
                                :min="fromDate"
                                max="{{ date('Y-m-d') }}"
                                class="py-2 px-3 block w-full border-gray-300 shadow-sm rounded-lg text-sm 
                                        focus:border-orange-500 focus:ring-orange-500"
                            >
                        </div>
                    </div>
                </div>

                {{-- Status Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select 
                        wire:model.live="filterStatus"
                        class="py-2 px-3 block w-full border-gray-300 shadow-sm rounded-lg text-sm 
                                focus:border-orange-500 focus:ring-orange-500">
                        <option value="">All</option>
                        <option value="0">Ongoing</option>
                        <option value="1">Disinfecting</option>
                    </select>
                </div>

            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 p-4 border-t border-gray-200">
                <button 
                    wire:click="clearFilters"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 
                           rounded-lg hover:bg-gray-50 transition">
                    Clear Filters
                </button>
                <button 
                    wire:click="applyFilters"
                    @click="show = false"
                    class="px-4 py-2 text-sm font-medium text-white bg-orange-500 
                           rounded-lg hover:bg-orange-600 transition">
                    Apply
                </button>
            </div>
        </div>
    </div>
</div>