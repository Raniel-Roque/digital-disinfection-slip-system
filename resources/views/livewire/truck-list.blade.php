<div class="max-w-full bg-white border border-gray-200 rounded-xl shadow-sm p-4 m-4">

    {{-- Search + Filter --}}
    <div class="mb-4 flex items-center gap-3">

        {{-- Search Bar --}}
        <div class="relative w-full">
            <label class="sr-only">Search</label>
            <input type="text" wire:model.live="search"
                class="py-2 px-3 ps-9 block w-full border-gray-200 shadow-sm rounded-lg sm:text-sm 
                        focus:border-blue-500 focus:ring-blue-500"
                placeholder="Search...">
            <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-3">
                <svg class="size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.3-4.3"></path>
                </svg>
            </div>
        </div>

        {{-- Filter Button --}}
        <x-submit-button wire:click="$toggle('showFilters')"
            class="w-auto px-4 py-2 flex items-center gap-2 whitespace-nowrap bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M6 12h12m-7 8h2" />
            </svg>
            Filter
        </x-submit-button>
    </div>

    {{-- Filter Modal --}}
    <x-filter-modal />

    {{-- Disinfection Slip Details Modal --}}
    <livewire:disinfection-slip />

    {{-- Table --}}
    <div wire:poll class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-center">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-xs font-medium text-gray-500 uppercase text-center w-[40%]">
                        Plate #
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-xs font-medium text-gray-500 uppercase text-center w-[40%]">
                        Status
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-xs font-medium text-gray-500 uppercase text-center w-[20%]">
                        Action
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">
                @forelse ($slips as $slip)
                    <tr class="hover:bg-gray-100 transition">

                        {{-- Plate # --}}
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 text-center w-[40%]">
                            {{ $slip->truck->plate_number ?? 'N/A' }}
                        </td>

                        {{-- Status --}}
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 text-center w-[40%]">
                            @php
                                $status = $slip->status;

                                $statusMap = [
                                    0 => ['label' => 'Ongoing', 'color' => 'bg-red-500'],
                                    1 => ['label' => 'Disinfecting', 'color' => 'bg-orange-500'],
                                    2 => ['label' => 'Completed', 'color' => 'bg-green-500'],
                                ];
                            @endphp

                            <span class="flex items-center justify-center gap-2">
                                <span class="w-3 h-3 rounded-full {{ $statusMap[$status]['color'] }}"></span>
                                <span class="font-medium">{{ $statusMap[$status]['label'] }}</span>
                            </span>
                        </td>

                        {{-- Action --}}
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center w-[20%]">
                            <x-submit-button
                                wire:click="$dispatch('open-disinfection-details', { id: {{ $slip->id }} })"
                                color="orange" class="w-auto px-4 whitespace-nowrap" type="button">
                                View Details
                            </x-submit-button>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-4 text-gray-500 text-center">
                            No truck slips found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="pt-4">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Showing
                {{ $slips->firstItem() }} – {{ $slips->lastItem() }}
                of
                {{ $slips->total() }} entries
            </div>

            <nav class="flex items-center space-x-1" aria-label="Pagination">
                @if ($slips->onFirstPage())
                    <button
                        class="p-2.5 min-w-10 inline-flex justify-center items-center rounded-full text-gray-400 bg-gray-50 cursor-not-allowed">«</button>
                @else
                    <button wire:click="previousPage"
                        class="p-2.5 min-w-10 inline-flex justify-center items-center rounded-full text-gray-700 hover:bg-gray-100">«</button>
                @endif

                @php
                    $current = $slips->currentPage();
                    $last = $slips->lastPage();

                    if ($last <= 3) {
                        $start = 1;
                        $end = $last;
                    } else {
                        if ($current === 1) {
                            $start = 1;
                            $end = 3;
                        } elseif ($current === $last) {
                            $start = $last - 2;
                            $end = $last;
                        } else {
                            $start = $current - 1;
                            $end = $current + 1;
                        }
                    }
                @endphp

                @for ($i = $start; $i <= $end; $i++)
                    @if ($i === $current)
                        <button
                            class="min-w-10 py-2.5 px-4 rounded-full bg-gray-100 text-gray-800 text-sm">{{ $i }}</button>
                    @else
                        <button wire:click="gotoPage({{ $i }})"
                            class="min-w-10 py-2.5 px-4 rounded-full hover:bg-gray-100 text-gray-800 text-sm">
                            {{ $i }}
                        </button>
                    @endif
                @endfor

                @if ($slips->hasMorePages())
                    <button wire:click="nextPage"
                        class="p-2.5 min-w-10 inline-flex justify-center items-center rounded-full text-gray-700 hover:bg-gray-100">»</button>
                @else
                    <button
                        class="p-2.5 min-w-10 inline-flex justify-center items-center rounded-full text-gray-400 bg-gray-50 cursor-not-allowed">»</button>
                @endif
            </nav>
        </div>
    </div>

</div>
