@php
    $status = $selectedSlip?->status ?? null;
    // Status: 0 = Ongoing, 1 = Disinfecting, 2 = Completed
    
    // Header class based on status
    $headerClass = '';
    if ($status == 0) {
        $headerClass = 'border-t-4 border-t-red-500 bg-red-50';
    } elseif ($status == 1) {
        $headerClass = 'border-t-4 border-t-orange-500 bg-orange-50';
    } elseif ($status == 2) {
        $headerClass = 'border-t-4 border-t-green-500 bg-green-50';
    }
@endphp

@if ($selectedSlip)
    {{-- MAIN DETAILS MODAL --}}
    <x-modals.modal-template show="showDetailsModal"
        max-width="max-w-3xl"
        header-class="{{ $headerClass }}">
        <x-slot name="titleSlot">
            {{ strtoupper($selectedSlip->location->location_name . ' DISINFECTION SLIP DETAILS') }}
        </x-slot>

        @if ($selectedSlip)

            {{-- Sub Header --}}
            <div class="border-b border-gray-200 px-6 py-2 bg-gray-50 -mx-6 -mt-6 mb-2">
                <div class="grid grid-cols-[1fr_1fr_auto] gap-4 items-start text-xs">
                    <div>
                        <div class="font-semibold text-gray-500 mb-0.5">Date:</div>
                        <div class="text-gray-900">{{ $selectedSlip->created_at->format('M d, Y') }}</div>
                </div>
                    <div>
                        <div class="font-semibold text-gray-500 mb-0.5">Slip No:</div>
                        <div class="text-gray-900 font-semibold">{{ $selectedSlip->slip_id }}</div>
            </div>
                </div>
            </div>

            {{-- Body Fields --}}
            <div class="space-y-0 -mx-6">
                {{-- Plate No --}}
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                    <div class="font-semibold text-gray-500">Plate No:</div>
                    <div class="text-gray-900">
                        @if ($selectedSlip->truck)
                            {{ $selectedSlip->truck->plate_number }}
                            @if ($selectedSlip->truck->trashed())
                                <span class="text-red-600 font-semibold">(Deleted)</span>
                            @endif
                        @else
                            <span class="text-red-600 font-semibold">(Deleted)</span>
                        @endif
                    </div>
                </div>

            {{-- Driver --}}
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                    <div class="font-semibold text-gray-500">Driver:</div>
                    <div class="text-gray-900">
                        {{ $selectedSlip->driver?->first_name . ' ' . $selectedSlip->driver?->last_name ?? 'N/A' }}
                    </div>
                </div>

                {{-- Origin --}}
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                    <div class="font-semibold text-gray-500">Origin:</div>
                    <div class="text-gray-900">
                        {{ $selectedSlip->location->location_name ?? 'N/A' }}
            </div>
                </div>

                {{-- Destination --}}
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                    <div class="font-semibold text-gray-500">Destination:</div>
                    <div class="text-gray-900">
                        {{ $selectedSlip->destination->location_name ?? 'N/A' }}
                    </div>
                </div>

                {{-- Completion Date (only when completed) --}}
                @if ($status == 2 && $selectedSlip->completed_at)
                    <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                        <div class="font-semibold text-gray-500">End Date:</div>
                        <div class="text-gray-900">
                            {{ \Carbon\Carbon::parse($selectedSlip->completed_at)->format('M d, Y - h:i A') }}
                        </div>
                    </div>
                @endif

                {{-- Attachment --}}
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs @if ($status == 2 && $selectedSlip->completed_at) bg-gray-100 @else bg-white @endif">
                    <div class="font-semibold text-gray-500">Attachment:</div>
                    <div class="text-gray-900">
                        @php
                            $attachments = $selectedSlip->attachments();
                            $attachmentCount = $attachments->count();
                        @endphp
                        @if ($attachmentCount > 0)
                            @php
                                $firstAttachment = $attachments->first();
                            @endphp
                            <button wire:click="openAttachmentModal('{{ $firstAttachment->file_path }}')"
                                class="text-orange-500 hover:text-orange-600 underline cursor-pointer">
                                See Attachment{{ $attachmentCount > 1 ? 's (' . $attachmentCount . ')' : '' }}
                            </button>
                        @else
                            N/A
                        @endif
                    </div>
                </div>

                {{-- Reason --}}
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs @if ($status == 2 && $selectedSlip->completed_at) bg-white @else bg-gray-100 @endif">
                    <div class="font-semibold text-gray-500">Reason:</div>
                    <div class="text-gray-900 wrap-break-words min-w-0" style="word-break: break-word; overflow-wrap: break-word;">
                        <div class="whitespace-pre-wrap">{{ $selectedSlip->reason_for_disinfection ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            {{-- Sub Footer --}}
            <div class="border-t border-gray-200 px-6 py-2 bg-gray-50 -mx-6 -mb-6 mt-2">
                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <div class="font-semibold text-gray-500 mb-0.5">Hatchery Guard:</div>
                        <div class="text-gray-900">
                            {{ $selectedSlip->hatcheryGuard?->first_name . ' ' . $selectedSlip->hatcheryGuard?->last_name ?? 'N/A' }}
                        </div>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-500 mb-0.5">Received By:</div>
                        <div class="text-gray-900">
                            {{ $selectedSlip->receivedGuard?->first_name && $selectedSlip->receivedGuard?->last_name
                                ? $selectedSlip->receivedGuard->first_name . ' ' . $selectedSlip->receivedGuard->last_name
                                : 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>
        @else
            <p class="text-gray-500 text-center">No details available.</p>
        @endif

        {{-- Footer --}}
        <x-slot name="footer">
            <div class="flex justify-end w-full gap-2">
                <x-buttons.submit-button wire:click="closeDetailsModal" color="white">
                    Close
                </x-buttons.submit-button>

                {{-- Edit Button --}}
                @if ($this->canEdit())
                    <x-buttons.submit-button wire:click="openEditModal" color="blue">
                        Edit
                    </x-buttons.submit-button>
                @endif
            </div>
        </x-slot>

    </x-modals.modal-template>

    {{-- Attachment Modal --}}
    @if ($attachmentFile)
        @php
            $fileUrl = Storage::url($attachmentFile);
            $extension = strtolower(pathinfo($attachmentFile ?? '', PATHINFO_EXTENSION));
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        @endphp

        <x-modals.modal-template show="showAttachmentModal" title="Attachment Preview" max-width="max-w-xl">

            @if (in_array($extension, $imageExtensions))
                {{-- IMAGE PREVIEW ONLY --}}
                <img src="{{ $fileUrl }}"
                    class="border shadow-md max-h-[50vh] max-w-full object-contain mx-auto rounded-lg"
                    alt="Attachment Preview">
            @else
                {{-- NO PREVIEW – ONLY LINK --}}
                <p class="text-sm text-gray-600 text-center p-4">
                    This file type cannot be previewed.<br>
                    <a href="{{ $fileUrl }}" target="_blank" class="text-orange-500 font-semibold underline hover:cursor-pointer cursor-pointer">
                        Download attachment
                    </a>
                </p>
            @endif

            <x-slot name="footer">
                <div class="flex justify-end space-x-3 w-full">

                    {{-- Back Button (always visible) --}}
                    <x-buttons.submit-button wire:click="closeAttachmentModal" color="white">
                        Back
                    </x-buttons.submit-button>

                    {{-- Remove Attachment button (only if admin and not completed) --}}
                    @if ($this->canRemoveAttachment())
                        <x-buttons.submit-button wire:click="confirmRemoveAttachment" color="red">
                            Remove Attachment
                        </x-buttons.submit-button>
                    @endif

                </div>
            </x-slot>

        </x-modals.modal-template>
    @endif

    {{-- Remove Attachment Confirmation Modal --}}
    <x-modals.modal-template show="showRemoveAttachmentConfirmation" title="REMOVE ATTACHMENT?" max-width="max-w-md">
        <div class="text-center py-4">
            <p class="text-gray-700 mb-2">Are you sure you want to remove this attachment?</p>
            <p class="text-sm text-red-600 font-semibold">This action cannot be undone!</p>
            <p class="text-sm text-gray-600">The file will be permanently deleted.</p>
        </div>

        <x-slot name="footer">
            <x-buttons.submit-button wire:click="$set('showRemoveAttachmentConfirmation', false)" color="white">
                Cancel
            </x-buttons.submit-button>
            <x-buttons.submit-button wire:click="removeAttachment" color="red">
                Yes, Remove Attachment
            </x-buttons.submit-button>
        </x-slot>
    </x-modals.modal-template>
@endif
