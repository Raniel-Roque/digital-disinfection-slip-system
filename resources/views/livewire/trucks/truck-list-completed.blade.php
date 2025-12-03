<div class="max-w-full bg-white border border-gray-200 rounded-xl shadow-sm p-4 m-4">

    {{-- Search Only --}}
    <div class="mb-4 flex items-center gap-3">
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
    </div>

    {{-- Disinfection Slip Details Modal --}}
    <livewire:trucks.disinfection-slip />

    {{-- Card List --}}
    <div wire:poll class="space-y-3">

        @forelse ($slips as $slip)
            @php
                $statusMap = [
                    0 => ['label' => 'Ongoing', 'color' => 'border-red-500 bg-red-50'],
                    1 => ['label' => 'Disinfecting', 'color' => 'border-orange-500 bg-orange-50'],
                    2 => ['label' => 'Completed', 'color' => 'border-green-500 bg-green-50'],
                ];
                $status = $slip->status;
            @endphp

            {{-- Card (Now Clickable) --}}
            <div wire:click="$dispatch('open-disinfection-details', { id: {{ $slip->id }} })"
                class="p-4 border-l-4 rounded-lg shadow-sm transition hover:shadow-md cursor-pointer flex justify-between items-center {{ $statusMap[$status]['color'] }}">

                {{-- Left Side Info --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-y-2 text-sm">

                    {{-- Status Badge --}}
                    <div
                        class="col-span-2 md:col-span-4 px-3 py-1 text-xs font-semibold text-white rounded-full w-fit
                                {{ $status === 0 ? 'bg-red-500' : ($status === 1 ? 'bg-orange-500' : 'bg-green-500') }}">
                        {{ $statusMap[$status]['label'] }}
                    </div>

                    <div class="font-semibold text-gray-600">Slip ID:</div>
                    <div class="text-gray-800">{{ $slip->slip_id }}</div>

                    <div class="font-semibold text-gray-600">Plate #:</div>
                    <div class="text-gray-800">{{ $slip->truck->plate_number ?? 'N/A' }}</div>

                    <div class="font-semibold text-gray-600">Completed At:</div>
                    <div class="text-gray-800">
                        @if ($slip->completed_at)
                            <div class="flex flex-col">
                                <span>{{ \Carbon\Carbon::parse($slip->completed_at)->format('M d, Y') }}</span>
                                <span
                                    class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($slip->completed_at)->format('h:i A') }}</span>
                            </div>
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </div>
                </div>

                {{-- Right Side - Arrow Icon --}}
                <div class="flex items-center">
                    <svg class="size-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </div>

        @empty
            <div class="text-center py-6 text-gray-500">
                No truck slips found.
            </div>
        @endforelse

    </div>

    {{-- Pagination --}}
    <x-buttons.nav-pagination :paginator="$slips" />
</div>
