<div class="grid grid-cols-1 gap-4">

    {{-- From Date Input --}}
    <div x-data="{
        updateToDateMin() {
            const toInput = $el.closest('.grid').querySelector('[x-ref=&quot;toDateInput&quot;]');
            const fromInput = $el.querySelector('[x-ref=&quot;fromDateInput&quot;]');
            if (toInput && fromInput && fromInput.value) {
                toInput.min = fromInput.value;
                // Clear to date if it's now invalid
                if (toInput.value && toInput.value < fromInput.value) {
                    toInput.value = '';
                    $wire.set('filterCreatedTo', '');
                }
            } else {
                toInput.min = '';
            }
        }
    }">
        <label class="block text-sm font-medium text-gray-700 mb-1">Deleted From Date</label>
        <input type="date" wire:model.live="filterCreatedFrom"
            x-ref="fromDateInput"
            @change="updateToDateMin()"
            max="<?php echo date('Y-m-d'); ?>"
            class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
    </div>

    {{-- To Date Input --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Deleted To Date</label>
        <input type="date" wire:model.live="filterCreatedTo"
            x-ref="toDateInput"
            x-init="const fromInput = $el.closest('.grid').querySelector('[x-ref=&quot;fromDateInput&quot;]');
                if (fromInput && fromInput.value) {
                    $el.min = fromInput.value;
                }"
            max="<?php echo date('Y-m-d'); ?>"
            class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
    </div>

</div>
