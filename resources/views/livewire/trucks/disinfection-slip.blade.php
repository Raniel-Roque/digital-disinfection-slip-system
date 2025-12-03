@php
    use Illuminate\Support\Facades\Auth;
    $isHatcheryAssigned = Auth::id() === $selectedSlip?->hatchery_guard_id;
    $isNotCompleted = $selectedSlip?->status != 2;
@endphp

<div>
    {{-- MAIN DETAILS MODAL --}}
    <x-modals.modal-template show="showDetailsModal"
        title="{{ strtoupper($selectedSlip?->location?->location_name . ' DISINFECTION SLIP DETAILS') }}"
        max-width="max-w-3xl">

        @if ($selectedSlip)

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
                        <x-forms.searchable-dropdown wire-model="truck_id" :options="$trucks->pluck('plate_number', 'id')"
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
                        <x-forms.searchable-dropdown wire-model="destination_id" :options="$locations->pluck('location_name', 'id')"
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
                        <x-forms.searchable-dropdown wire-model="driver_id" :options="$drivers->pluck('full_name', 'id')"
                            placeholder="Select driver..." search-placeholder="Search drivers..." />
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
                        {{ $selectedSlip->receivedGuard?->first_name . ' ' . $selectedSlip->receivedGuard?->last_name ?? 'N/A' }}
                    </div>
                </div>

                <div class="grid grid-cols-3 mb-2">
                    <div class="font-semibold text-gray-700">Completion Date:</div>
                    <div class="col-span-2 text-gray-900">
                        {{ $selectedSlip->completed_at ? $selectedSlip->completed_at->format('M d, Y h:i A') : 'N/A' }}
                    </div>
                </div>

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

            @endif
        @else
            <p class="text-gray-500 text-center">No details available.</p>
        @endif

        {{-- Footer --}}
        <x-slot name="footer">
            @if (!$isEditing)
                <x-buttons.submit-button wire:click="closeDetailsModal" color="white">
                    Close
                </x-buttons.submit-button>

                @if ($isHatcheryAssigned && $isNotCompleted)
                    <x-buttons.submit-button wire:click="editDetailsModal" color="blue">
                        Edit
                    </x-buttons.submit-button>
                @endif
            @else
                <div class="flex justify-between w-full">
                    <div>
                        <x-buttons.submit-button wire:click="$set('showDeleteConfirmation', true)" color="red">
                            Delete
                        </x-buttons.submit-button>
                    </div>
                    <div class="flex gap-2">
                        <x-buttons.submit-button wire:click="$set('showCancelConfirmation', true)" color="white">
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
        message="Delete this disinfection slip?" :details="'Slip No: <span class=\'font-semibold\'>' . ($selectedSlip?->slip_id ?? '') . '</span>'" warning="This action cannot be undone!"
        onConfirm="deleteSlip" />

    {{-- Attachment Modal --}}
    <x-modals.attachment show="showAttachmentModal" :file="$attachmentFile" />

</div>
