@props([
    'availableActions' => [],
    'availableModelTypes' => [],
    'availableUserTypes' => [],
    'filterActionOptions' => [],
    'filterModelTypeOptions' => [],
])

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- Action Filter --}}
    <div x-data="{
        open: false,
        searchTerm: '',
        options: @js($availableActions),
        selected: @entangle('filterAction'),
        get filteredOptions() {
            if (!this.searchTerm) return this.options;
            const term = this.searchTerm.toLowerCase();
            const filtered = {};
            for (const [key, value] of Object.entries(this.options)) {
                if (String(value).toLowerCase().includes(term)) {
                    filtered[key] = value;
                }
            }
            return filtered;
        },
        get displayText() {
            if (!this.selected || this.selected.length === 0) return 'Select actions...';
            if (this.selected.length === 1) {
                return this.options[this.selected[0]] || this.selected[0];
            }
            return this.selected.length + ' selected';
        },
        toggleSelection(key) {
            if (!Array.isArray(this.selected)) {
                this.selected = [];
            }
            const index = this.selected.indexOf(key);
            if (index > -1) {
                this.selected.splice(index, 1);
            } else {
                this.selected.push(key);
            }
        },
        isSelected(key) {
            return Array.isArray(this.selected) && this.selected.includes(key);
        },
        closeDropdown() {
            this.open = false;
            this.searchTerm = '';
        }
    }" x-ref="actionDropdown" @click.outside="closeDropdown()">
        <div class="flex items-center justify-between mb-1">
            <label class="block text-sm font-medium text-gray-700">Action</label>
            <button type="button" x-on:click="selected = []; $wire.set('filterAction', [])"
                x-show="selected && selected.length > 0"
                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                Clear
            </button>
        </div>
        <div class="relative">
            <button type="button" x-on:click="open = !open"
                class="inline-flex justify-between w-full px-4 py-2 text-sm font-medium bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500 cursor-pointer"
                :class="{ 'ring-2 ring-blue-500': open }">
                <span :class="{ 'text-gray-400': !selected || selected.length === 0 }" x-text="displayText"></span>
                <svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5 ml-2 -mr-1 transition-transform"
                    :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M6.293 9.293a1 1 0 011.414 0L10 11.586l2.293-2.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>
            <div x-show="open" x-transition class="absolute right-0 mt-2 w-full rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 p-1 z-50"
                style="display: none;" x-cloak @click.stop>
                <input type="text" x-model="searchTerm" placeholder="Search actions..."
                    class="block w-full px-4 py-2 text-gray-800 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 mb-1"
                    autocomplete="off">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="[key, label] in Object.entries(filteredOptions)" :key="key">
                        <a href="#" x-on:click.prevent="toggleSelection(key)"
                            class="block px-4 py-2 text-gray-700 hover:bg-gray-100 cursor-pointer rounded-md transition-colors"
                            :class="{ 'bg-blue-50 text-blue-700': isSelected(key) }">
                            <div class="flex items-center justify-between">
                                <span x-text="label"></span>
                                <svg x-show="isSelected(key)" class="w-5 h-5 text-blue-600" fill="currentColor"
                                    viewBox="0 0 20 20" style="display: none;">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Model Type Filter --}}
    <div x-data="{
        open: false,
        searchTerm: '',
        options: @js($availableModelTypes),
        selected: @entangle('filterModelType'),
        get filteredOptions() {
            if (!this.searchTerm) return this.options;
            const term = this.searchTerm.toLowerCase();
            const filtered = {};
            for (const [key, value] of Object.entries(this.options)) {
                if (String(value).toLowerCase().includes(term)) {
                    filtered[key] = value;
                }
            }
            return filtered;
        },
        get displayText() {
            if (!this.selected || this.selected.length === 0) return 'Select model type...';
            if (this.selected.length === 1) {
                return this.options[this.selected[0]] || this.selected[0];
            }
            return this.selected.length + ' selected';
        },
        toggleSelection(key) {
            if (!Array.isArray(this.selected)) {
                this.selected = [];
            }
            const index = this.selected.indexOf(key);
            if (index > -1) {
                this.selected.splice(index, 1);
            } else {
                this.selected.push(key);
            }
        },
        isSelected(key) {
            return Array.isArray(this.selected) && this.selected.includes(key);
        },
        closeDropdown() {
            this.open = false;
            this.searchTerm = '';
        }
    }" x-ref="modelTypeDropdown" @click.outside="closeDropdown()">
        <div class="flex items-center justify-between mb-1">
            <label class="block text-sm font-medium text-gray-700">Model Type</label>
            <button type="button" x-on:click="selected = []; $wire.set('filterModelType', [])"
                x-show="selected && selected.length > 0"
                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                Clear
            </button>
        </div>
        <div class="relative">
            <button type="button" x-on:click="open = !open"
                class="inline-flex justify-between w-full px-4 py-2 text-sm font-medium bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500 cursor-pointer"
                :class="{ 'ring-2 ring-blue-500': open }">
                <span :class="{ 'text-gray-400': !selected || selected.length === 0 }" x-text="displayText"></span>
                <svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5 ml-2 -mr-1 transition-transform"
                    :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M6.293 9.293a1 1 0 011.414 0L10 11.586l2.293-2.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>
            <div x-show="open" x-transition class="absolute right-0 mt-2 w-full rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 p-1 z-50"
                style="display: none;" x-cloak @click.stop>
                <input type="text" x-model="searchTerm" placeholder="Search records..."
                    class="block w-full px-4 py-2 text-gray-800 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 mb-1"
                    autocomplete="off">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="[key, label] in Object.entries(filteredOptions)" :key="key">
                        <a href="#" x-on:click.prevent="toggleSelection(key)"
                            class="block px-4 py-2 text-gray-700 hover:bg-gray-100 cursor-pointer rounded-md transition-colors"
                            :class="{ 'bg-blue-50 text-blue-700': isSelected(key) }">
                            <div class="flex items-center justify-between">
                                <span x-text="label"></span>
                                <svg x-show="isSelected(key)" class="w-5 h-5 text-blue-600" fill="currentColor"
                                    viewBox="0 0 20 20" style="display: none;">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- User Type Filter (Multi-select for SuperAdmin only) --}}
    <div x-data="{
        open: false,
        searchTerm: '',
        options: @js($availableUserTypes),
        selected: @entangle('filterUserType'),
        get filteredOptions() {
            if (!this.searchTerm) return this.options;
            const term = this.searchTerm.toLowerCase();
            const filtered = {};
            for (const [key, value] of Object.entries(this.options)) {
                if (String(value).toLowerCase().includes(term)) {
                    filtered[key] = value;
                }
            }
            return filtered;
        },
        get displayText() {
            if (!this.selected || this.selected.length === 0) return 'Select user role...';
            if (this.selected.length === 1) {
                return this.options[this.selected[0]] || this.selected[0];
            }
            return this.selected.length + ' selected';
        },
        toggleSelection(key) {
            if (!Array.isArray(this.selected)) {
                this.selected = [];
            }
            const numKey = Number(key);
            const index = this.selected.findIndex(v => Number(v) === numKey);
            if (index > -1) {
                this.selected.splice(index, 1);
            } else {
                this.selected.push(numKey);
            }
        },
        isSelected(key) {
            if (!Array.isArray(this.selected)) return false;
            return this.selected.some(v => Number(v) === Number(key));
        },
        closeDropdown() {
            this.open = false;
            this.searchTerm = '';
        }
    }" x-ref="userTypeDropdown" @click.outside="closeDropdown()">
        <div class="flex items-center justify-between mb-1">
            <label class="block text-sm font-medium text-gray-700">User Role</label>
            <button type="button" x-on:click="selected = []; $wire.set('filterUserType', [])"
                x-show="selected && selected.length > 0"
                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                Clear
            </button>
        </div>
        <div class="relative">
            <button type="button" x-on:click="open = !open"
                class="inline-flex justify-between w-full px-4 py-2 text-sm font-medium bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500 cursor-pointer"
                :class="{ 'ring-2 ring-blue-500': open }">
                <span :class="{ 'text-gray-400': !selected || selected.length === 0 }" x-text="displayText"></span>
                <svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5 ml-2 -mr-1 transition-transform"
                    :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M6.293 9.293a1 1 0 011.414 0L10 11.586l2.293-2.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>
            <div x-show="open" x-transition class="absolute right-0 mt-2 w-full rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 p-1 z-50"
                style="display: none;" x-cloak @click.stop>
                <input type="text" x-model="searchTerm" placeholder="Search roles..."
                    class="block w-full px-4 py-2 text-gray-800 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 mb-1"
                    autocomplete="off">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="[key, label] in Object.entries(filteredOptions)" :key="key">
                        <a href="#" x-on:click.prevent="toggleSelection(key)"
                            class="block px-4 py-2 text-gray-700 hover:bg-gray-100 cursor-pointer rounded-md transition-colors"
                            :class="{ 'bg-blue-50 text-blue-700': isSelected(key) }">
                            <div class="flex items-center justify-between">
                                <span x-text="label"></span>
                                <svg x-show="isSelected(key)" class="w-5 h-5 text-blue-600" fill="currentColor"
                                    viewBox="0 0 20 20" style="display: none;">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- From Date Input --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
        <input type="date" wire:model.live="filterCreatedFrom"
            class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
    </div>

    {{-- To Date Input --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
        <input type="date" wire:model.live="filterCreatedTo"
            class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
    </div>

</div>
