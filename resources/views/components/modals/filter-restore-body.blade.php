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
            @input="
                const toInput = $refs.toDateInput;
                if (toInput) {
                    // Set To Date min to From Date
                    toInput.min = $el.value || '';
                    // Clear To Date if it's before the new From Date
                    if (toInput.value && $el.value && toInput.value < $el.value) {
                        toInput.value = '';
                        $wire.set('filterCreatedTo', '');
                    }
                    // Validate From Date doesn't exceed To Date
                    if (toInput.value && $el.value && $el.value > toInput.value) {
                        $el.value = '';
                        $wire.set('filterCreatedFrom', '');
                    }
                }
            "
            :max="$wire.filterCreatedTo || '<?php echo date('Y-m-d'); ?>'"
            class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
    </div>

    {{-- To Date Input --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Deleted To Date</label>
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
                    // Clear To Date if it's before From Date
                    if (fromInput.value && $el.value && $el.value < fromInput.value) {
                        $el.value = '';
                        $wire.set('filterCreatedTo', '');
                    }
                }
            "
            :min="$wire.filterCreatedFrom || ''"
            max="<?php echo date('Y-m-d'); ?>"
            class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
    </div>

    {{-- Exclude Deleted Items Toggle (full width) - SUPERADMIN ONLY --}}
    @if (Auth::user()->user_type === 2)
        <div class="border-t border-gray-200 pt-4 mt-2">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" wire:model.live="excludeDeletedItems"
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                <span class="text-sm font-medium text-gray-700">
                    Exclude items with deleted related items
                </span>
            </label>
            <p class="text-xs text-gray-500 mt-1 ml-7">
                When enabled, hides items where any related item has been deleted
            </p>
        </div>
    @endif

</div>
