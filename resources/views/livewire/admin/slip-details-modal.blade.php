@if ($selectedSlip)
    {{-- MAIN DETAILS MODAL --}}
    <x-modals.modal-template show="showDetailsModal"
        title="{{ strtoupper($selectedSlip->location->location_name . ' DISINFECTION SLIP DETAILS') }}"
        max-width="max-w-3xl">

        {{-- Status Badge --}}
        <div class="grid grid-cols-3 mb-2">
            <div class="font-semibold text-gray-700">Status:</div>
            <div class="col-span-2">
                @if ($selectedSlip->status == 0)
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
                        Pending
                    </span>
                @elseif ($selectedSlip->status == 1)
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                        Disinfecting
                    </span>
                @elseif ($selectedSlip->status == 2)
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                        Completed
                    </span>
                @endif
            </div>
        </div>

        {{-- Date --}}
        <div class="grid grid-cols-3 mb-2">
            <div class="font-semibold text-gray-700">Date:</div>
            <div class="col-span-2 text-gray-900">
                {{ $selectedSlip->created_at->format('M d, Y - h:i A') }}
            </div>
        </div>

        {{-- Slip Number --}}
        <div class="grid grid-cols-3 mb-2">
            <div class="font-semibold text-gray-700">Slip No:</div>
            <div class="col-span-2 text-gray-900 font-semibold">
                {{ $selectedSlip->slip_id }}
            </div>
        </div>

        {{-- Plate --}}
        <div class="grid grid-cols-3 mb-2">
            <div class="font-semibold text-gray-700">Plate No:</div>
            <div class="col-span-2 text-gray-900">
                @if ($isEditing)
                    <x-forms.searchable-dropdown wire-model="truck_id" :options="$this->trucks->pluck('plate_number', 'id')"
                        placeholder="Select plate number..." search-placeholder="Search plates..." />
                @else
                    {{ $selectedSlip->truck->plate_number ?? 'N/A' }}
                @endif
            </div>
        </div>

        {{-- Destination --}}
        <div class="grid grid-cols-3 mb-2">
            <div class="font-semibold text-gray-700">Destination:</div>
            <div class="col-span-2 text-gray-900">
                @if ($isEditing)
                    <x-forms.searchable-dropdown wire-model="destination_id" :options="$this->locations->pluck('location_name', 'id')"
                        placeholder="Select destination..." search-placeholder="Search locations..." />
                @else
                    {{ $selectedSlip->destination->location_name ?? 'N/A' }}
                @endif
            </div>
        </div>

        {{-- Driver --}}
        <div class="grid grid-cols-3 mb-2">
            <div class="font-semibold text-gray-700">Driver Name:</div>
            <div class="col-span-2 text-gray-900">
                @if ($isEditing)
                    <x-forms.searchable-dropdown wire-model="driver_id" :options="$this->drivers->pluck('full_name', 'id')" placeholder="Select driver..."
                        search-placeholder="Search drivers..." />
                @else
                    {{ $selectedSlip->driver?->first_name . ' ' . $selectedSlip->driver?->last_name ?? 'N/A' }}
                @endif
            </div>
        </div>

        {{-- Reason / textarea expands when editing --}}
        <div class="grid grid-cols-3 mb-2">
            <div class="font-semibold text-gray-700">Reason:</div>
            <div class="col-span-2 text-gray-900">
                @if ($isEditing)
                    <textarea wire:model="reason_for_disinfection" class="w-full border rounded px-2 py-1 text-sm" rows="6"></textarea>
                @else
                    {{ $selectedSlip->reason_for_disinfection ?? 'N/A' }}
                @endif
            </div>
        </div>

        {{-- Hidden Display Info when NOT editing --}}
        @if (!$isEditing)

            <div class="grid grid-cols-3 mb-2">
                <div class="font-semibold text-gray-700">Hatchery Guard:</div>
                <div class="col-span-2 text-gray-900">
                    {{ $selectedSlip->hatcheryGuard?->first_name . ' ' . $selectedSlip->hatcheryGuard?->last_name ?? 'N/A' }}
                </div>
            </div>

            <div class="grid grid-cols-3 mb-2">
                <div class="font-semibold text-gray-700">Received By:</div>
                <div class="col-span-2 text-gray-900">
                    {{ $selectedSlip->receivedGuard?->first_name && $selectedSlip->receivedGuard?->last_name ? $selectedSlip->receivedGuard->first_name . ' ' . $selectedSlip->receivedGuard->last_name : 'N/A' }}
                </div>
            </div>

            <div class="grid grid-cols-3 mb-2">
                <div class="font-semibold text-gray-700">Completion Date:</div>
                <div class="col-span-2 text-gray-900">
                    {{ $selectedSlip->completed_at ? \Carbon\Carbon::parse($selectedSlip->completed_at)->format('M d, Y - h:i A') : 'N/A' }}
                </div>
            </div>

            {{-- Attachment Section --}}
            <div class="grid grid-cols-3 mb-2">
                <div class="font-semibold text-gray-700">Attachment:</div>
                <div class="col-span-2">
                    @if ($selectedSlip->attachment)
                        <button wire:click="openAttachmentModal('{{ $selectedSlip->attachment->file_path }}')"
                            class="text-orange-500 hover:text-orange-600 underline cursor-pointer">
                            See Attachment
                        </button>
                    @else
                        N/A
                    @endif
                </div>
            </div>

        @endif

        {{-- Footer --}}
        <x-slot name="footer">
            @if (!$isEditing)
                <div class="flex justify-end w-full gap-2">
                    <x-buttons.submit-button wire:click="closeDetailsModal" color="white">
                        Close
                    </x-buttons.submit-button>

                    {{-- Edit Button (Only if not completed) --}}
                    @if ($this->canEdit())
                        <x-buttons.submit-button wire:click="editDetailsModal" color="blue">
                            Edit
                        </x-buttons.submit-button>
                    @endif
                </div>
            @else
                <div class="flex justify-between w-full">
                    <div>
                        {{-- Delete Button (Only if not completed) --}}
                        @if ($this->canDelete())
                            <x-buttons.submit-button wire:click="confirmDeleteSlip" color="red">
                                Delete
                            </x-buttons.submit-button>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <x-buttons.submit-button wire:click="confirmCancelEdit" color="white">
                            Cancel
                        </x-buttons.submit-button>

                        <x-buttons.submit-button wire:click="save" color="green">
                            Save
                        </x-buttons.submit-button>
                    </div>
                </div>
            @endif
        </x-slot>

    </x-modals.modal-template>

    {{-- Cancel Confirmation Modal --}}
    <x-modals.unsaved-confirmation show="showCancelConfirmation" title="DISCARD CHANGES?"
        message="Are you sure you want to cancel?" warning="All unsaved changes will be lost." onConfirm="cancelEdit"
        confirmText="Yes, Discard Changes" cancelText="Continue Editing" />

    {{-- Delete Confirmation Modal --}}
    <x-modals.delete-confirmation show="showDeleteConfirmation" title="DELETE SLIP?"
        message="Delete this disinfection slip?" :details="'Slip No: <span class=\'font-semibold\'>' . ($selectedSlip->slip_id ?? '') . '</span>'" warning="This action cannot be undone!"
        onConfirm="deleteSlip" />

    {{-- Attachment Modal --}}
    @if ($attachmentFile)
        @php
            $fileUrl = Storage::url($attachmentFile);
            $extension = strtolower(pathinfo($attachmentFile ?? '', PATHINFO_EXTENSION));
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        @endphp

        <x-modals.modal-template show="showAttachmentModal" title="Attachment Preview" max-width="max-w-4xl">

            @if (in_array($extension, $imageExtensions))
                {{-- IMAGE PREVIEW ONLY --}}
                <img src="{{ $fileUrl }}" class="border shadow-md max-h-[80vh] object-contain mx-auto rounded-lg"
                    alt="Attachment Preview">
            @else
                {{-- NO PREVIEW – ONLY LINK --}}
                <p class="text-sm text-gray-600 text-center p-4">
                    This file type cannot be previewed.<br>
                    <a href="{{ $fileUrl }}" target="_blank" class="text-orange-500 font-semibold underline">
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
