@props([
    'wireModel' => null,
    'options' => [],
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

if (!$wireModel) {
    throw new \Exception('wireModel or wire-model attribute is required for searchable-dropdown component');
    }
@endphp

<div class="w-full" x-data="{
    open: false,
    dropdownId: 'dropdown_' + Math.random().toString(36).substr(2, 9),
    multiple: {{ $multiple ? 'true' : 'false' }},
    get displayText() {
        const selected = $wire.get('{{ $wireModel }}');
        if (!selected) return '{{ $placeholder }}';
        const options = @js($options);

        if (this.multiple) {
            // Multiple selection mode
            if (Array.isArray(selected) && selected.length > 0) {
                if (selected.length === 1) {
                    return options[selected[0]] || '{{ $placeholder }}';
                }
                return selected.length + ' selected';
            }
            return '{{ $placeholder }}';
        } else {
            // Single selection mode
            return options[selected] || '{{ $placeholder }}';
        }
    },
    toggleDropdown() {
        if (this.open) {
            this.closeDropdown();
        } else {
            this.$dispatch('close-all-dropdowns', { except: this.dropdownId });
            this.open = true;
        }
    },
    closeDropdown() {
        this.open = false;
        @if($searchProperty)
        // Clear search when closing
        $wire.set('{{ $searchProperty }}', '');
        @endif
    },
    init() {
        this.$watch('open', value => {
            if (value) {
                this.$nextTick(() => {
                    const searchInput = this.$el.querySelector('[data-search-input]');
                    if (searchInput) {
                        searchInput.focus();
                    }
                });
            }
        });
    }
}"
    @close-all-dropdowns.window="if ($event.detail.except !== dropdownId) { closeDropdown() }"
    @click.outside="if (open) closeDropdown()">

    @if ($label)
        <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        <!-- Dropdown Button -->
        <button type="button" @click="toggleDropdown()" :disabled="{{ $disabled ? 'true' : 'false' }}"
            class="inline-flex justify-between w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            :class="{ 'ring-2 ring-blue-500': open }">
            <span x-text="displayText"
                :class="{
                    'text-gray-400': multiple ?
                        (!$wire.get('{{ $wireModel }}') || $wire.get('{{ $wireModel }}').length === 0) :
                        !$wire.get('{{ $wireModel }}')
                }"></span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 ml-2 -mr-1 transition-transform"
                :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd"
                    d="M6.293 9.293a1 1 0 011.414 0L10 11.586l2.293-2.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                    clip-rule="evenodd" />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <div x-show="open" x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute right-0 mt-2 w-full rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 p-1 space-y-1 z-50"
            style="display: none;">

            <!-- Search Input -->
            @if ($searchProperty)
                <input data-search-input type="text" wire:model.live.debounce.300ms="{{ $searchProperty }}"
                    @keydown.escape="closeDropdown()"
                    class="block w-full px-4 py-2 text-gray-800 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="{{ $searchPlaceholder }}" autocomplete="off">
            @endif

            <!-- Options List - Filtered server-side by Livewire -->
            @php
                // Calculate max height based on maxShown (approximately 40px per item)
                $maxHeight = $maxShown * 40 . 'px';
            @endphp
            <div class="overflow-y-auto" style="max-height: {{ $maxHeight }};"
                wire:key="options-list-{{ $wireModel }}-{{ md5(json_encode($options)) }}">
                @forelse($options as $value => $label)
                    <a href="#"
                        @if ($multiple) @click.prevent="
                                const current = $wire.get('{{ $wireModel }}') || [];
                                const val = {{ $value }};
                                const index = current.indexOf(val);
                                if (index > -1) {
                                    current.splice(index, 1);
                                    $wire.set('{{ $wireModel }}', current);
                                } else {
                                    $wire.set('{{ $wireModel }}', [...current, val]);
                                }
                            "
                        @else
                            wire:click.prevent="$set('{{ $wireModel }}', {{ $value }})"
                            @click="closeDropdown()" @endif
                        class="block px-4 py-2 text-gray-700 hover:bg-gray-100 active:bg-blue-100 cursor-pointer rounded-md transition-colors"
                        x-bind:class="@if ($multiple) ($wire.get('{{ $wireModel }}') || []).includes({{ $value }}) ? 'bg-blue-50 text-blue-700' : ''
                            @else
                                $wire.get('{{ $wireModel }}') == {{ $value }} ? 'bg-blue-50 text-blue-700' : '' @endif">
                        <div class="flex items-center justify-between">
                            <span>{{ $label }}</span>
                            @if ($multiple)
                                <template
                                    x-if="($wire.get('{{ $wireModel }}') || []).includes({{ $value }})">
                                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                </template>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="px-4 py-6 text-center text-sm text-gray-500">
                        No results found
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    @if ($error)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
