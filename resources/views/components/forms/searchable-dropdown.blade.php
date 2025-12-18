@props([
    'wireModel' => null,
    'options' => [],
    'allOptions' => null, // Unfiltered options for display (falls back to options if not provided)
    'searchProperty' => null,
    'placeholder' => 'Select an option...',
    'searchPlaceholder' => 'Search...',
    'label' => null,
    'error' => null,
    'disabled' => false,
    'multiple' => false,
    'maxShown' => 5,
    'max-shown' => 5, // Support both kebab-case and camelCase
])

@php
    // Blade automatically converts kebab-case to camelCase, but handle both for safety
    // Also check attributes in case it's passed as wire-model
$wireModel = $wireModel ?? $attributes->get('wire-model');
$searchProperty = $searchProperty ?? $attributes->get('search-property');
$searchPlaceholder = $searchPlaceholder ?? ($attributes->get('search-placeholder') ?? 'Search...');
$maxShown = $maxShown ?? $attributes->get('max-shown', 5);
// Use allOptions if provided (for display), otherwise fall back to options
$allOptions = $allOptions ?? ($attributes->get('all-options') ?? $options);

if (!$wireModel) {
    throw new \Exception('wireModel or wire-model attribute is required for searchable-dropdown component');
}

// Generate unique ID for this dropdown instance
$dropdownId = 'dropdown_' . str_replace(['.', '[', ']'], '_', $wireModel) . '_' . uniqid();

// Calculate max height based on maxShown (approximately 40px per item)
$maxHeight = $maxShown * 40 . 'px';
@endphp

<div class="w-full" wire:key="dropdown-{{ $dropdownId }}">
    @if ($label)
        <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
        </label>
    @endif

    {{-- Alpine.js for open state, search, and client-side filtering --}}
    @if ($multiple)
    <div class="relative" x-data="{
        open: false,
        searchTerm: '',
        allOptions: @js($allOptions),
        localSelection: [],
        init() {
            this.localSelection = $wire.get('{{ $wireModel }}') || [];
        },
        get filteredOptions() {
            if (!this.searchTerm) {
                return this.allOptions;
            }
            const term = this.searchTerm.toLowerCase();
            const filtered = {};
            for (const [key, value] of Object.entries(this.allOptions)) {
                if (String(value).toLowerCase().includes(term)) {
                    filtered[key] = value;
                }
            }
            return filtered;
        },
        closeDropdown() {
            // Don't sync to Livewire on close for multiselect
            this.open = false;
            this.searchTerm = '';
        },
        syncToLivewire() {
            // Sync to Livewire - call this when Apply is clicked
            $wire.set('{{ $wireModel }}', [...this.localSelection]);
        },
        updateSelection(val) {
            // Only update local selection, don't sync to Livewire yet
            const index = this.localSelection.indexOf(val);
            if (index > -1) {
                this.localSelection.splice(index, 1);
            } else {
                this.localSelection.push(val);
            }
        },
        handleFocusIn(event) {
            // Close if focus moves to another input, dropdown, or form element
            const target = event.target;
            const container = $refs.dropdownContainer;
            if (this.open && !container.contains(target)) {
                // Check if target is an input, select, textarea, or button (another dropdown)
                if (target.tagName === 'INPUT' ||
                    target.tagName === 'SELECT' ||
                    target.tagName === 'TEXTAREA' ||
                    (target.tagName === 'BUTTON' && target.closest('[x-data]') && !container.contains(target.closest('[x-data]')))) {
                    this.closeDropdown();
                }
            }
        }
    }" @else <div class="relative" x-data="{
        open: false,
        searchTerm: '',
        allOptions: @js($allOptions),
        get filteredOptions() {
            if (!this.searchTerm) {
                return this.allOptions;
            }
            const term = this.searchTerm.toLowerCase();
            const filtered = {};
            for (const [key, value] of Object.entries(this.allOptions)) {
                if (String(value).toLowerCase().includes(term)) {
                    filtered[key] = value;
                }
            }
            return filtered;
        },
        closeDropdown() {
            this.open = false;
            this.searchTerm = '';
        },
        handleFocusIn(event) {
            // Close if focus moves to another input, dropdown, or form element
            const target = event.target;
            const container = $refs.dropdownContainer;
            if (this.open && !container.contains(target)) {
                // Check if target is an input, select, textarea, or button (another dropdown)
                if (target.tagName === 'INPUT' ||
                    target.tagName === 'SELECT' ||
                    target.tagName === 'TEXTAREA' ||
                    (target.tagName === 'BUTTON' && target.closest('[x-data]') && !container.contains(target.closest('[x-data]')))) {
                    this.closeDropdown();
                }
            }
        }
    }" @endif
            x-ref="dropdownContainer"
            @if ($multiple) @sync-selections.window="syncToLivewire()" @endif
            @click.outside="closeDropdown()"
            @focusin.window="handleFocusIn($event)">
            <!-- Dropdown Button -->
            <button type="button" x-on:click="open = !open" @disabled($disabled)
                class="inline-flex justify-between w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed hover:cursor-pointer cursor-pointer"
                :class="{ 'ring-2 ring-blue-500': open }">
                <span
                    :class="{ 'text-gray-400': @if ($multiple) localSelection.length === 0 @else !$wire.get('{{ $wireModel }}') @endif }">
                    <span
                        x-text="
                    (function() {
                        @if ($multiple) const selected = localSelection;
                            if (selected.length === 0) return '{{ $placeholder }}';
                            const allOptions = @js($allOptions);
                            if (selected.length === 1) {
                                return allOptions[selected[0]] || '{{ $placeholder }}';
                            }
                            return selected.length + ' selected';
                        @else
                            const selected = $wire.get('{{ $wireModel }}');
                            if (!selected) return '{{ $placeholder }}';
                            const allOptions = @js($allOptions);
                            const filteredOptions = @js($options);
                            return allOptions[selected] || filteredOptions[selected] || '{{ $placeholder }}'; @endif
                    })()
                "></span>
                </span>
                <svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5 ml-2 -mr-1 transition-transform"
                    :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd"
                        d="M6.293 9.293a1 1 0 011.414 0L10 11.586l2.293-2.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="open" @if ($multiple) wire:ignore @endif
                x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-full rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 p-1 space-y-1"
                style="display: none; z-index: 9999;" x-cloak @click.stop>

                <!-- Search Input -->
                @if ($searchProperty)
                    <input type="text" x-model="searchTerm" x-on:keydown.escape="closeDropdown()"
                        class="block w-full px-4 py-2 text-gray-800 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="{{ $searchPlaceholder }}" autocomplete="off">
                @endif

                <!-- Options List - Filtered client-side by Alpine.js -->
                <div class="overflow-y-auto" style="max-height: {{ $maxHeight }};">
                    <template x-for="[value, label] in Object.entries(filteredOptions)" :key="value">
                        <a href="#"
                            @if ($multiple) x-on:click.prevent="updateSelection(Number(value))"
                        @else
                            x-on:click.prevent="
                                $wire.set('{{ $wireModel }}', Number(value));
                                closeDropdown();
                            " @endif
                            class="block px-4 py-2 text-gray-700 hover:bg-gray-100 active:bg-blue-100 cursor-pointer rounded-md transition-colors"
                            :class="@if ($multiple) localSelection.includes(Number(value)) @else $wire.get('{{ $wireModel }}') == Number(value) @endif
                                ? 'bg-blue-50 text-blue-700' : ''">
                            <div class="flex items-center justify-between">
                                <span x-text="label"></span>
                                @if ($multiple)
                                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20"
                                        x-show="localSelection.includes(Number(value))" style="display: none;">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>
                        </a>
                    </template>
                    <div x-show="Object.keys(filteredOptions).length === 0"
                        class="px-4 py-6 text-center text-sm text-gray-500" style="display: none;">
                        No results found
                    </div>
                </div>
            </div>
        </div>

        @if ($error)
            <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
        @endif
</div>
