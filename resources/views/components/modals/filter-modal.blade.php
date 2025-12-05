<div x-data="{ show: @entangle('showFilters') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title"
    role="dialog" aria-modal="true" style="display: none;">

    {{-- Background overlay --}}
    <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/80 transition-opacity" @click="show = false">
    </div>

    {{-- Modal panel --}}
    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div x-show="show" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 w-full sm:max-w-4xl">

            {{-- Header --}}
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title">
                        Filter Options
                    </h3>
                    <button type="button" @click="show = false"
                        class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Content --}}
            <div class="bg-white px-4 py-5 sm:p-6">
                {{ $filters }}
            </div>

            {{-- Footer --}}
            <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-3">
                <x-buttons.submit-button wire:click="applyFilters" @click="show = false"
                    class="inline-flex w-full justify-center px-4 py-2 text-sm font-medium text-white bg-orange-500 
                           rounded-lg hover:bg-orange-600 transition sm:w-auto">
                    Apply Filters
                </x-buttons.submit-button>
                <x-buttons.submit-button wire:click="clearFilters" @click="show = false" color="white"
                    class="mt-3 inline-flex w-full justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 
                           rounded-lg hover:bg-gray-50 transition sm:mt-0 sm:w-auto">
                    Clear All
                </x-buttons.submit-button>
            </div>
        </div>
    </div>
</div>
