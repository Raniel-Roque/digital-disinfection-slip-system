@props(['message' => '', 'type' => 'info'])

<div x-show="show" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform scale-90 translate-y-2"
     x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
     x-transition:leave-end="opacity-0 transform scale-90 translate-y-2"
     class="w-full max-w-md mx-auto shadow-lg rounded-lg pointer-events-auto overflow-hidden"
     :class="{
        'bg-white border-l-4 border-green-500': type === 'success',
        'bg-white border-l-4 border-red-500': type === 'error',
        'bg-white border-l-4 border-yellow-500': type === 'warning',
        'bg-white border-l-4 border-blue-500': type === 'info',
     }">
    
    <div class="p-4">
        <div class="flex items-center">
            {{-- ICON --}}
            <div class="shrink-0">
                {{-- Success Icon --}}
                <div class="h-6 w-6 rounded-full bg-green-100 flex items-center justify-center" x-show="type === 'success'" style="display: none;">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                {{-- Error Icon --}}
                <div class="h-6 w-6 rounded-full bg-red-100 flex items-center justify-center" x-show="type === 'error'" style="display: none;">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>

                {{-- Warning Icon --}}
                <div class="h-6 w-6 rounded-full bg-yellow-100 flex items-center justify-center" x-show="type === 'warning'" style="display: none;">
                    <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                {{-- Info Icon --}}
                <div class="h-6 w-6 rounded-full bg-blue-100 flex items-center justify-center" x-show="type === 'info'" style="display: none;">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            {{-- MESSAGE --}}
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium text-gray-900" x-text="message">
                </p>
            </div>

            {{-- CLOSE BUTTON --}}
            <div class="ml-4 shrink-0 flex">
                <button @click="show = false" 
                        class="inline-flex rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors">
                    <span class="sr-only">Close</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" 
                              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" 
                              clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Progress bar animation (optional) --}}
    <div class="h-1 bg-gray-100">
        <div x-show="show" 
             class="h-full transition-all duration-2500ms ease-linear"
             :class="{
                'bg-green-500': type === 'success',
                'bg-red-500': type === 'error',
                'bg-yellow-500': type === 'warning',
                'bg-blue-500': type === 'info',
             }"
             x-init="setTimeout(() => $el.style.width = '0%', 10); $el.style.width = '100%'">
        </div>
    </div>
</div>