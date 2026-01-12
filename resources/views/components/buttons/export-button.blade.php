@props([
    'type' => 'dropdown', // 'dropdown' or 'trucks'
    'showCreate' => false, // Show Create option (mobile only)
    'showRestore' => false, // Show Restore option (mobile only)
    'showDeleted' => false, // State for restore button (Back to Active vs Restore)
])

@php
    $hasMobileActions = $showCreate || ($showRestore && !$showDeleted);
    $showThreeDotsOnMobile = $type === 'trucks' || $hasMobileActions;
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button @click="open = !open" title="{{ $type === 'trucks' ? 'Options' : 'Download' }}"
        class="inline-flex items-center justify-center w-10 h-10 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 hover:cursor-pointer cursor-pointer">
        @if($type === 'trucks')
            {{-- Three vertical dots icon (always for trucks) --}}
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
            </svg>
        @elseif($hasMobileActions)
            {{-- Show 3-dots on mobile, download icon on desktop when actions are merged --}}
            {{-- Three vertical dots icon (mobile) --}}
            <svg class="w-5 h-5 md:hidden" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
            </svg>
            {{-- Download icon (desktop) --}}
            <svg class="w-5 h-5 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
        @else
            {{-- Download icon (when no mobile actions) --}}
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
        @endif
    </button>

    <div x-show="open" x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
        style="display: none;" x-cloak>
        <div class="py-1">
            {{-- Mobile: Create option --}}
            @if($showCreate)
                <button wire:click="openCreateModal" @click="open = false"
                    class="md:hidden w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:cursor-pointer cursor-pointer">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Create
                    </div>
                </button>
            @endif

            {{-- Mobile: Restore option (only when NOT in deleted mode) --}}
            @if($showRestore && !$showDeleted)
                <button wire:click="toggleDeletedView" wire:loading.attr="disabled" wire:target="toggleDeletedView" @click="open = false"
                    class="md:hidden w-full text-left block px-4 py-2 text-sm text-orange-600 hover:bg-gray-100 hover:cursor-pointer cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    <div class="flex items-center">
                        <svg wire:loading.remove wire:target="toggleDeletedView" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        <svg wire:loading wire:target="toggleDeletedView" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="toggleDeletedView">Restore</span>
                        <span wire:loading wire:target="toggleDeletedView">Loading...</span>
                    </div>
                </button>
            @endif

            @if($showCreate || ($showRestore && !$showDeleted))
                {{-- Divider line between mobile actions and export options --}}
                <div class="md:hidden border-t border-gray-200 my-1"></div>
            @endif

            <a href="#" wire:click.prevent="exportCSV" @click="open = false"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:cursor-pointer cursor-pointer">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Export as CSV
                </div>
            </a>
            <a href="#" wire:click.prevent="openPrintView" @click="open = false"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:cursor-pointer cursor-pointer">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                        </path>
                    </svg>
                    Print / PDF
                </div>
            </a>

            @if($type === 'trucks')
                {{-- Divider line --}}
                <div class="border-t border-gray-200 my-1"></div>
                
                {{-- Reasons option --}}
                <a href="#" wire:click.prevent="openReasonsModal" @click="open = false"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:cursor-pointer cursor-pointer">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Reasons
                    </div>
                </a>
            @endif
        </div>
    </div>
</div>