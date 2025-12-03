@props([
    'wireModel' => null,
    'options' => [],
    'placeholder' => 'Select an option...',
    'searchPlaceholder' => 'Search...',
    'label' => null,
    'error' => null,
    'disabled' => false,
])

<div class="w-full" x-data="{
    open: false,
    search: '',
    selected: @entangle($wireModel),
    options: @js($options),
    placeholder: '{{ $placeholder }}',
    get displayText() {
        if (this.selected && this.options[this.selected]) {
            return this.options[this.selected];
        }
        return this.placeholder;
    },
    get filteredOptions() {
        if (!this.search) {
            return Object.entries(this.options);
        }
        return Object.entries(this.options).filter(([value, label]) =>
            label.toLowerCase().includes(this.search.toLowerCase())
        );
    },
    selectOption(value, label) {
        this.selected = value;
        this.search = '';
        this.open = false;
    },
    init() {
        this.$watch('open', value => {
            if (value) {
                this.$nextTick(() => {
                    this.$refs.searchInput.focus();
                });
            } else {
                this.search = '';
            }
        });
    }
}">
    @if ($label)
        <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        <!-- Dropdown Button -->
        <button type="button" @click="open = !open" :disabled="{{ $disabled ? 'true' : 'false' }}"
            class="inline-flex justify-between w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            :class="{ 'ring-2 ring-blue-500': open }">
            <span x-text="displayText" :class="{ 'text-gray-400': !selected }"></span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 ml-2 -mr-1 transition-transform"
                :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd"
                    d="M6.293 9.293a1 1 0 011.414 0L10 11.586l2.293-2.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                    clip-rule="evenodd" />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <div x-show="open" @click.outside="open = false" x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute right-0 mt-2 w-full rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 p-1 space-y-1 z-50"
            style="display: none;">

            <!-- Search Input -->
            <input x-ref="searchInput" x-model="search" @keydown.escape="open = false"
                class="block w-full px-4 py-2 text-gray-800 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                type="text" :placeholder="'{{ $searchPlaceholder }}'" autocomplete="off">

            <!-- Options List -->
            <div class="max-h-60 overflow-y-auto">
                <template x-for="[value, label] in filteredOptions" :key="value">
                    <a href="#" @click.prevent="selectOption(value, label)"
                        class="block px-4 py-2 text-gray-700 hover:bg-gray-100 active:bg-blue-100 cursor-pointer rounded-md"
                        :class="{ 'bg-blue-50 text-blue-700': selected === value }">
                        <span x-text="label"></span>
                    </a>
                </template>

                <!-- No Results -->
                <div x-show="filteredOptions.length === 0" class="px-4 py-6 text-center text-sm text-gray-500">
                    No results found
                </div>
            </div>
        </div>
    </div>

    @if ($error)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
