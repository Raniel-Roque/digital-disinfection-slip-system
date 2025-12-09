@php
    use Illuminate\Support\Facades\Auth;
    $isHatcheryAssigned = Auth::id() === $selectedSlip?->hatchery_guard_id;
    $isReceivingGuard = Auth::id() === $selectedSlip?->received_guard_id;
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

<div>
    {{-- MAIN DETAILS MODAL --}}
    <x-modals.modal-template show="showDetailsModal"
        max-width="max-w-3xl"
        header-class="{{ $headerClass }}">
        <x-slot name="titleSlot">
            {{ strtoupper($selectedSlip?->location?->location_name . ' DISINFECTION SLIP DETAILS') }}
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
                    <div class="flex items-center">
                        <button wire:click="openReportModal" type="button"
                            class="p-1.5 text-red-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                            title="Report">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                            </svg>
                        </button>
            </div>
                </div>
            </div>

            {{-- Body Fields --}}
            <div class="space-y-0 -mx-6">
                {{-- Plate No --}}
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                    <div class="font-semibold text-gray-500">Plate No:</div>
                    <div class="text-gray-900">
                    @if ($isEditing)
                        <x-forms.searchable-dropdown wire-model="truck_id" :options="$this->truckOptions"
                            search-property="searchTruck" placeholder="Select plate number..."
                            search-placeholder="Search plates..." />
                    @else
                        {{ $selectedSlip->truck->plate_number ?? 'N/A' }}
                    @endif
                </div>
            </div>

            {{-- Driver --}}
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                    <div class="font-semibold text-gray-500">Driver:</div>
                    <div class="text-gray-900">
                    @if ($isEditing)
                        <x-forms.searchable-dropdown wire-model="driver_id" :options="$this->driverOptions"
                            search-property="searchDriver" placeholder="Select driver..."
                            search-placeholder="Search drivers..." />
                    @else
                        {{ $selectedSlip->driver?->first_name . ' ' . $selectedSlip->driver?->last_name ?? 'N/A' }}
                    @endif
                </div>
            </div>

                {{-- Origin --}}
                @if (!$isEditing)
                    <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                        <div class="font-semibold text-gray-500">Origin:</div>
                        <div class="text-gray-900">
                            {{ $selectedSlip->location->location_name ?? 'N/A' }}
            </div>
                    </div>
                @endif

                {{-- Destination --}}
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                    <div class="font-semibold text-gray-500">Destination:</div>
                    <div class="text-gray-900">
                        @if ($isEditing)
                            <x-forms.searchable-dropdown wire-model="destination_id" :options="$this->locationOptions"
                                search-property="searchDestination" placeholder="Select destination..."
                                search-placeholder="Search locations..." />
                        @else
                            {{ $selectedSlip->destination->location_name ?? 'N/A' }}
                        @endif
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
                @if (!$isEditing)
                    <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                        <div class="font-semibold text-gray-500">Attachment:</div>
                        <div class="text-gray-900">
                        @if ($selectedSlip->attachment)
                            <button wire:click="openAttachmentModal('{{ $selectedSlip->attachment->file_path }}')"
                                class="text-orange-500 hover:text-orange-600 underline cursor-pointer">
                                See Attachment
                            </button>
                        @elseif ($this->canManageAttachment())
                            <button wire:click="openAddAttachmentModal"
                                class="text-blue-500 hover:text-blue-600 underline cursor-pointer">
                                Add Attachment
                            </button>
                        @else
                            N/A
                        @endif
                        </div>
                    </div>
                @endif

                {{-- Reason --}}
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs @if ($isEditing && $status == 2 && $selectedSlip->completed_at) bg-gray-100 @else bg-white @endif">
                    <div class="font-semibold text-gray-500">Reason:</div>
                    <div class="text-gray-900 wrap-break-words min-w-0" style="word-break: break-word; overflow-wrap: break-word;">
                        @if ($isEditing)
                            <textarea wire:model.live="reason_for_disinfection" class="w-full border rounded px-2 py-2 text-sm" rows="6"></textarea>
                        @else
                            <div class="whitespace-pre-wrap">{{ $selectedSlip->reason_for_disinfection ?? 'N/A' }}</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Sub Footer --}}
            @if (!$isEditing)
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
            @endif
        @else
            <p class="text-gray-500 text-center">No details available.</p>
        @endif

        {{-- Footer --}}
        <x-slot name="footer">
            @if (!$isEditing)
                <div class="flex justify-end w-full gap-2">
                        <x-buttons.submit-button wire:click="closeDetailsModal" color="white">
                            Close
                        </x-buttons.submit-button>

                        {{-- Edit Button (Only hatchery guard, matching location, and not completed) --}}
                        @if ($this->canEdit())
                            <x-buttons.submit-button wire:click="editDetailsModal" color="blue">
                                Edit
                            </x-buttons.submit-button>
                        @endif

                        {{-- Disinfecting Button (Status 0 -> 1, only on incoming at destination location) --}}
                        @if ($this->canStartDisinfecting())
                            <x-buttons.submit-button wire:click="$set('showDisinfectingConfirmation', true)" color="blue">
                                Start Disinfecting
                            </x-buttons.submit-button>
                        @endif

                        {{-- Complete Button (Status 1 -> 2, only on incoming at destination location) --}}
                        @if ($this->canComplete())
                            <x-buttons.submit-button wire:click="$set('showCompleteConfirmation', true)" color="green">
                                Complete Disinfection
                            </x-buttons.submit-button>
                        @endif
                </div>
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

                        <x-buttons.submit-button 
                            wire:click="save" 
                            color="green" 
                            wire:loading.attr="disabled" 
                            wire:target="save"
                            :disabled="!$this->hasChanges">
                            <span wire:loading.remove wire:target="save">Save</span>
                            <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                                Saving...
                            </span>
                        </x-buttons.submit-button>
                    </div>
                </div>
            @endif
        </x-slot>

    </x-modals.modal-template>

    {{-- Cancel Confirmation Modal --}}
    <x-modals.unsaved-confirmation show="showCancelConfirmation" title="DISCARD CHANGES?"
        message="Are you sure you want to cancel?" warning="All unsaved changes will be lost." onConfirm="cancelEdit"
        confirmText="Cancel" cancelText="Continue" />

    {{-- Delete Confirmation Modal --}}
    <x-modals.delete-confirmation show="showDeleteConfirmation" title="DELETE SLIP?"
        message="Delete this disinfection slip?" :details="'Slip No: <span class=\'font-semibold\'>' . ($selectedSlip?->slip_id ?? '') . '</span>'" warning="This action cannot be undone!"
        onConfirm="deleteSlip" />

    {{-- Disinfecting Confirmation Modal --}}
    <x-modals.modal-template show="showDisinfectingConfirmation" title="START DISINFECTING?" max-width="max-w-md">
        <div class="text-center py-4">
            <p class="text-gray-700 mb-2">Start disinfecting this truck?</p>
            <p class="text-sm text-gray-600">You will be assigned as the receiving guard.</p>
        </div>

        <x-slot name="footer">
            <x-buttons.submit-button wire:click="$set('showDisinfectingConfirmation', false)" color="white" wire:loading.attr="disabled" wire:target="startDisinfecting">
                Cancel
            </x-buttons.submit-button>
            <x-buttons.submit-button wire:click="startDisinfecting" color="blue" wire:loading.attr="disabled" wire:target="startDisinfecting">
                <span wire:loading.remove wire:target="startDisinfecting">Yes, Start Disinfecting</span>
                <span wire:loading wire:target="startDisinfecting" class="inline-flex items-center gap-2">
                    Starting...
                </span>
            </x-buttons.submit-button>
        </x-slot>
    </x-modals.modal-template>

    {{-- Complete Confirmation Modal --}}
    <x-modals.modal-template show="showCompleteConfirmation" title="COMPLETE DISINFECTION?" max-width="max-w-md">
        <div class="text-center py-4">
            <p class="text-gray-700 mb-2">Mark this disinfection as completed?</p>
            <p class="text-sm text-gray-600">This action cannot be undone.</p>
        </div>

        <x-slot name="footer">
            <x-buttons.submit-button wire:click="$set('showCompleteConfirmation', false)" color="white" wire:loading.attr="disabled" wire:target="completeDisinfection">
                Cancel
            </x-buttons.submit-button>
            <x-buttons.submit-button wire:click="completeDisinfection" color="green" wire:loading.attr="disabled" wire:target="completeDisinfection">
                <span wire:loading.remove wire:target="completeDisinfection">Yes, Complete</span>
                <span wire:loading wire:target="completeDisinfection" class="inline-flex items-center gap-2">
                    Completing...
                </span>
            </x-buttons.submit-button>
        </x-slot>
    </x-modals.modal-template>

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

    {{-- Attachment Modal --}}
    <x-modals.attachment show="showAttachmentModal" :file="$attachmentFile" :selectedSlip="$selectedSlip" />

    {{-- Add Attachment Modal --}}
    <x-modals.add-attachment show="showAddAttachmentModal" />

    {{-- Report Modal --}}
    <x-modals.modal-template show="showReportModal" title="REPORT DISINFECTION SLIP?" max-width="max-w-lg">
        <div class="py-4">
            <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-yellow-800 mb-1">Warning</p>
                        <p class="text-sm text-yellow-700">
                            You are about to report this disinfection slip. Please provide a detailed reason for your
                            report.
                            This will be reviewed by administrators.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <p class="text-sm text-gray-700 mb-2">
                    <span class="font-semibold">Slip No:</span> {{ $selectedSlip?->slip_id ?? 'N/A' }}
                </p>
                <p class="text-sm text-gray-700">
                    <span class="font-semibold">Plate No:</span> {{ $selectedSlip?->truck?->plate_number ?? 'N/A' }}
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Reason / Description <span class="text-red-500">*</span>
                </label>
                <textarea wire:model="reportDescription" rows="5"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm"
                    placeholder="Please describe the issue or reason for reporting this slip..."></textarea>
                @error('reportDescription')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Minimum 10 characters required.</p>
            </div>
        </div>

        <x-slot name="footer">
            <x-buttons.submit-button wire:click="closeReportModal" color="white" wire:loading.attr="disabled" wire:target="submitReport">
                Cancel
            </x-buttons.submit-button>
            <x-buttons.submit-button wire:click="submitReport" color="red" wire:loading.attr="disabled" wire:target="submitReport">
                <span wire:loading.remove wire:target="submitReport">Submit Report</span>
                <span wire:loading wire:target="submitReport" class="inline-flex items-center gap-2">
                    Submitting...
                </span>
                Submit Report
            </x-buttons.submit-button>
        </x-slot>
    </x-modals.modal-template>

</div>
