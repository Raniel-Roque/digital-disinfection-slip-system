@props([
    'availableStatuses' => [],
    'availableGuardTypes' => [],
    'showGuardTypeFilter' => true,
])

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- Status Filter using shared component --}}
    <x-filters.status-dropdown 
        label="Status"
        wireModel="filterStatus"
        :options="$availableStatuses"
        placeholder="Select status"
    />

    {{-- Guard Type Filter - Only if showGuardTypeFilter is enabled --}}
    @if ($showGuardTypeFilter)
    <x-filters.status-dropdown 
        label="Guard Type"
        wireModel="filterGuardType"
        :options="$availableGuardTypes"
        placeholder="Select guard type"
    />
    @endif

    {{-- From Date Input --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
        <input type="date" wire:model.live="filterCreatedFrom"
            x-ref="fromDateInput"
            @input="
                const toInput = $refs.toDateInput;
                if (toInput) {
                    // Clear To Date if it's before the new From Date
                    if (toInput.value && $el.value && toInput.value < $el.value) {
                        toInput.value = '';
                        $wire.set('filterCreatedTo', '');
                    }
                }
            "
            max="<?php echo date('Y-m-d'); ?>"
            class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
    </div>

    {{-- To Date Input --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
        <input type="date" wire:model.live="filterCreatedTo"
            x-ref="toDateInput"
            @input="
                const fromInput = $refs.fromDateInput;
                if (fromInput) {
                    // Clear From Date if it exceeds the new To Date
                    if (fromInput.value && $el.value && fromInput.value > $el.value) {
                        fromInput.value = '';
                        $wire.set('filterCreatedFrom', '');
                    }
                }
            "
            max="<?php echo date('Y-m-d'); ?>"
            class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
    </div>

</div>
