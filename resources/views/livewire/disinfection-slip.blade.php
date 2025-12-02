<div>

    {{-- MAIN DETAILS MODAL --}}
    <x-modal-template show="showDetailsModal"
        title="{{ strtoupper($selectedSlip?->location?->location_name . ' DISINFECTION SLIP DETAILS') }}"
        max-width="max-w-3xl">

        @if ($selectedSlip)

            {{-- Date + Slip Number --}}
            <div class="grid grid-cols-3 mb-2">
                <div class="font-semibold text-gray-700">Date:</div>
                <div class="col-span-2 text-gray-900">
                    {{ $selectedSlip->created_at->format('M d, Y - h:i') }}
                </div>
            </div>

            <div class="grid grid-cols-3 mb-2">
                <div class="font-semibold text-gray-700">Slip No:</div>
                <div class="col-span-2 text-gray-900 font-semibold">{{ $selectedSlip->slip_id }}</div>
            </div>

            {{-- Plate --}}
            <div class="grid grid-cols-3 mb-2">
                <div class="font-semibold text-gray-700">Plate No:</div>
                <div class="col-span-2 text-gray-900">{{ $selectedSlip->truck->plate_number ?? 'N/A' }}</div>
            </div>

            {{-- Destination --}}
            <div class="grid grid-cols-3 mb-2">
                <div class="font-semibold text-gray-700">Destination:</div>
                <div class="col-span-2 text-gray-900">{{ $selectedSlip->destination->location_name ?? 'N/A' }}</div>
            </div>

            {{-- Driver Name --}}
            <div class="grid grid-cols-3 mb-2">
                <div class="font-semibold text-gray-700">Driver Name:</div>
                <div class="col-span-2 text-gray-900">
                    {{ $selectedSlip->driver ? $selectedSlip->driver->first_name . ' ' . $selectedSlip->driver->last_name : 'N/A' }}
                </div>
            </div>

            {{-- Reason --}}
            <div class="grid grid-cols-3 mb-2">
                <div class="font-semibold text-gray-700">Reason:</div>
                <div class="col-span-2 text-gray-900">{{ $selectedSlip->reason_for_disinfection ?? 'N/A' }}</div>
            </div>

            {{-- Hatchery Guard --}}
            <div class="grid grid-cols-3 mb-2">
                <div class="font-semibold text-gray-700">Hatchery Guard:</div>
                <div class="col-span-2 text-gray-900">{{ $selectedSlip->hatcheryGuard->name ?? 'N/A' }}</div>
            </div>

            {{-- Received By --}}
            <div class="grid grid-cols-3 mb-2">
                <div class="font-semibold text-gray-700">Received By:</div>
                <div class="col-span-2 text-gray-900">{{ $selectedSlip->receivedGuard->name ?? 'N/A' }}</div>
            </div>

            {{-- Completion Date --}}
            <div class="grid grid-cols-3 mb-2">
                <div class="font-semibold text-gray-700">Completion Date:</div>
                <div class="col-span-2 text-gray-900">
                    {{ $selectedSlip->completed_at ? $selectedSlip->completed_at->format('M d, Y h:i A') : 'N/A' }}
                </div>
            </div>

            {{-- Attachment --}}
            <div class="grid grid-cols-3 mb-2">
                <div class="font-semibold text-gray-700">Attachment:</div>
                <div class="col-span-2">
                    @if ($selectedSlip->attachment)
                        <button wire:click="openAttachmentModal('{{ $selectedSlip->attachment->file_path }}')"
                            class="text-orange-500 hover:text-orange-600 underline">
                            See Attachment
                        </button>
                    @else
                        N/A
                    @endif
                </div>
            </div>
        @else
            <p class="text-gray-500 text-center">No details available.</p>
        @endif

        {{-- Footer --}}
        <x-slot name="footer">
            <button wire:click="closeDetailsModal"
                class="px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                Close
            </button>
        </x-slot>

    </x-modal-template>



    {{-- ATTACHMENT PREVIEW MODAL --}}
    @if ($showAttachmentModal)
        <x-modal-template show="showAttachmentModal" title="ATTACHMENT PREVIEW" max-width="max-w-xl">
            @if ($attachmentFile)
                <img src="{{ Storage::url($attachmentFile) }}" class="w-full rounded-lg shadow">
            @endif

            <x-slot name="footer">
                <button wire:click="closeAttachmentModal"
                    class="px-4 py-2 text-sm bg-gray-200 rounded-lg hover:bg-gray-300">
                    Close
                </button>
            </x-slot>
        </x-modal-template>
    @endif

</div>
