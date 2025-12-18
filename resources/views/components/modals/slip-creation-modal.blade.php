@props([
    'trucks' => collect(),
    'locations' => collect(),
    'drivers' => collect(),
    'truckOptions' => [],
    'locationOptions' => [],
    'driverOptions' => [],
    'isCreating' => false,
    'pendingAttachmentIds' => [],
])

{{-- CREATE MODAL --}}
<x-modals.modal-template show="showCreateModal" title="CREATE NEW DISINFECTION SLIP" max-width="max-w-3xl">

    {{-- Body Fields --}}
    <div class="space-y-0 -mx-6">
        {{-- Plate Number --}}
        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
            <div class="font-semibold text-gray-500">Plate No:<span class="text-red-500">*</span></div>
            <div class="text-gray-900">
                <x-forms.searchable-dropdown wire-model="truck_id" :options="$truckOptions" search-property="searchTruck"
                    placeholder="Select plate number..." search-placeholder="Search plates..." />
                @error('truck_id')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
        </div>

        {{-- Destination --}}
        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
            <div class="font-semibold text-gray-500">Destination:<span class="text-red-500">*</span></div>
            <div class="text-gray-900">
                <x-forms.searchable-dropdown wire-model="destination_id" :options="$locationOptions"
                    search-property="searchDestination" placeholder="Select destination..."
                    search-placeholder="Search locations..." />
                @error('destination_id')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
        </div>

        {{-- Driver Name --}}
        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
            <div class="font-semibold text-gray-500">Driver Name:<span class="text-red-500">*</span></div>
            <div class="text-gray-900">
                <x-forms.searchable-dropdown wire-model="driver_id" :options="$driverOptions" search-property="searchDriver"
                    placeholder="Select driver..." search-placeholder="Search drivers..." />
                @error('driver_id')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
        </div>

        {{-- Attachments --}}
        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
            <div class="font-semibold text-gray-500">Attachments:</div>
            <div class="text-gray-900">
                @php
                    $pendingCount = count($pendingAttachmentIds ?? []);
                @endphp
                @if ($pendingCount > 0)
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs text-gray-600">{{ $pendingCount }} photo(s) attached</span>
                        <button wire:click="openAddAttachmentModal"
                            class="text-blue-500 hover:text-blue-600 underline cursor-pointer text-xs">
                            + Add More
                        </button>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mb-2">
                        @foreach ($pendingAttachmentIds ?? [] as $attachmentId)
                            @php
                                $attachment = \App\Models\Attachment::find($attachmentId);
                                $currentUser = \Illuminate\Support\Facades\Auth::user();
                                $isAdminOrSuperAdmin = $currentUser && in_array($currentUser->user_type, [1, 2]); // 1 = Admin, 2 = SuperAdmin
                                $canDelete = $attachment && ($isAdminOrSuperAdmin || $attachment->user_id === \Illuminate\Support\Facades\Auth::id());
                            @endphp
                            @if ($attachment)
                                <div class="relative rounded-lg overflow-hidden shadow-md">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($attachment->file_path) }}" 
                                         class="w-full h-24 object-cover">
                                    @if ($canDelete)
                                        <button wire:click="confirmRemovePendingAttachment({{ $attachmentId }})" 
                                                class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white text-xs px-2 py-1 rounded">
                                            Remove
                                        </button>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
                <button wire:click="openAddAttachmentModal"
                    class="text-blue-500 hover:text-blue-600 underline cursor-pointer text-xs">
                    {{ $pendingCount > 0 ? 'Add More Attachment' : 'Add Attachment' }}
                </button>
            </div>
        </div>

        {{-- Reason for Disinfection --}}
        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
            <div class="font-semibold text-gray-500">Reason:</div>
            <div class="text-gray-900">
                <textarea wire:model="reason_for_disinfection"
                    class="w-full border rounded px-2 py-2 text-sm border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    rows="6" placeholder="Enter reason for disinfection..."></textarea>
                @error('reason_for_disinfection')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <x-slot name="footer">
        <div x-data="{ isCreating: @js($isCreating) }" class="flex justify-end gap-2">
        <x-buttons.submit-button wire:click="closeCreateModal" color="white" wire:loading.attr="disabled" wire:target="createSlip">
            Cancel
        </x-buttons.submit-button>

        <x-buttons.submit-button wire:click.prevent="createSlip" color="blue" wire:loading.attr="disabled" wire:target="createSlip"
                x-bind:disabled="isCreating">
            <span wire:loading.remove wire:target="createSlip">Create Slip</span>
            <span wire:loading wire:target="createSlip">Creating...</span>
        </x-buttons.submit-button>
        </div>
    </x-slot>

</x-modals.modal-template>

{{-- Cancel Confirmation Modal --}}
<x-modals.unsaved-confirmation show="showCancelCreateConfirmation" title="DISCARD CHANGES?"
    message="Are you sure you want to cancel?" warning="All unsaved changes will be lost." onConfirm="cancelCreate"
    confirmText="Discard" cancelText="Back" />

{{-- Remove Pending Attachment Confirmation Modal --}}
<x-modals.delete-confirmation show="showRemovePendingAttachmentConfirmation" title="DELETE PHOTO?"
    message="Are you sure you want to delete this photo?" warning="This action cannot be undone."
    onConfirm="removePendingAttachment" confirmText="Delete" cancelText="Cancel" />
