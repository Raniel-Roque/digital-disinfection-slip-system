@props(['reasons' => null, 'editingReasonId' => null, 'editingReasonText' => '', 'showUnsavedChangesConfirmation' => false, 'showSaveConfirmation' => false, 'savingReason' => false])

{{-- Reasons Settings Modal --}}
<x-modals.modal-template 
    show="showReasonsModal" 
    title="Reasons Settings" 
    maxWidth="max-w-3xl"
>
    <div class="space-y-4">
        {{-- Reasons List Container --}}
        <div class="bg-gray-50 rounded-lg border border-gray-200 divide-y divide-gray-200">
            @forelse($reasons as $index => $reason)
                <div class="flex items-center gap-3 p-3">
                    {{-- Reason Display/Edit --}}
                    <div class="flex-1 flex items-center gap-3">
                        @if($editingReasonId === $reason->id)
                            <input 
                                type="text" 
                                wire:model="editingReasonText"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                placeholder="Reason text"
                            >
                        @else
                            <span class="text-sm text-gray-900">{{ $reason->reason_text }}</span>
                        @endif
                    </div>
                    
                    {{-- Action Buttons --}}
                    <div class="flex items-center gap-2">
                        {{-- Edit/Save Button --}}
                        @if($editingReasonId === $reason->id)
                            <button
                                wire:click="saveReasonEdit"
                                type="button"
                                class="flex items-center justify-center w-9 h-9 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                                title="Save"
                            >
                                <svg wire:loading.remove wire:target="saveReasonEdit" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <svg wire:loading wire:target="saveReasonEdit" class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                        @else
                            <button
                                wire:click="startEditingReason({{ $reason->id }})"
                                type="button"
                                class="flex items-center justify-center w-9 h-9 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                title="Edit"
                            >
                                <svg wire:loading.remove wire:target="startEditingReason({{ $reason->id }})" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <svg wire:loading wire:target="startEditingReason({{ $reason->id }})" class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                        @endif

                        {{-- Disable/Enable Button --}}
                        @if($editingReasonId !== $reason->id)
                        <button
                            wire:click="toggleReasonDisabled({{ $reason->id }})"
                            type="button"
                            class="flex items-center justify-center w-9 h-9 rounded-lg transition-colors {{ $reason->disabled ? 'text-green-600 hover:bg-green-50' : 'text-yellow-600 hover:bg-yellow-50' }}"
                            title="{{ $reason->disabled ? 'Enable' : 'Disable' }}"
                        >
                            <span wire:loading.remove wire:target="toggleReasonDisabled({{ $reason->id }})">
                                @if($reason->disabled)
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                    </svg>
                                @endif
                            </span>
                            <svg wire:loading wire:target="toggleReasonDisabled({{ $reason->id }})" class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                        @endif

                        {{-- Delete Button (SuperAdmin only) --}}
                        @if($editingReasonId !== $reason->id && Auth::user()->user_type === 2)
                            <button
                                wire:click="confirmDeleteReason({{ $reason->id }})"
                                type="button"
                                class="flex items-center justify-center w-9 h-9 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                title="Delete"
                            >
                                <svg wire:loading.remove wire:target="confirmDeleteReason({{ $reason->id }})" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                <svg wire:loading wire:target="confirmDeleteReason({{ $reason->id }})" class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="mt-2 text-sm">No reasons found.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($reasons->hasPages())
            <div class="mt-4">
                {{ $reasons->links() }}
            </div>
        @endif

        {{-- Add New Reason Section --}}
        <div class="pt-4 border-t border-gray-200">
            <div class="flex gap-2">
                <input 
                    type="text" 
                    wire:model="newReasonText"
                    wire:keydown.enter="addNewReason"
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    placeholder="Enter new reason"
                >
                <button
                    wire:click="addNewReason"
                    type="button"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    :disabled="!$wire.newReasonText || $wire.newReasonText.trim() === ''"
                >
                    <span wire:loading.remove wire:target="addNewReason">Add</span>
                    <svg wire:loading wire:target="addNewReason" class="animate-spin h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading wire:target="addNewReason">Adding...</span>
                </button>
            </div>
            @error('newReasonText')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <x-slot name="footer">
        <x-buttons.submit-button 
            wire:click="attemptCloseReasonsModal"
            color="gray" 
            size="lg"
            :fullWidth="false"
        >
            Close
        </x-buttons.submit-button>
    </x-slot>
</x-modals.modal-template>

{{-- Unsaved Changes Confirmation Modal --}}
@if($showUnsavedChangesConfirmation)
    <div class="fixed inset-0 z-60 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/80 transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
            <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all w-full max-w-md sm:max-w-lg">
                <div class="bg-white px-4 py-5 sm:px-6 sm:py-6">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-yellow-100 rounded-full shrink-0">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <h3 class="ml-3 sm:ml-4 text-lg font-semibold text-gray-900">Unsaved Changes</h3>
                    </div>
                </div>

                <div class="px-4 py-3 sm:px-6 sm:py-4">
                    <p class="text-sm text-gray-600">
                        You have unsaved changes. Are you sure you want to close without saving?
                    </p>
                </div>

                <div class="px-4 py-3 sm:px-6 sm:py-4 bg-gray-50 flex flex-col-reverse sm:flex-row justify-end gap-3 sm:gap-3">
                    <x-buttons.submit-button
                        wire:click="$set('showUnsavedChangesConfirmation', false)"
                        color="gray"
                        size="lg"
                        :fullWidth="true"
                        class="sm:fullWidth"
                    >
                        Cancel
                    </x-buttons.submit-button>

                    <x-buttons.submit-button
                        wire:click="closeWithoutSaving"
                        color="red"
                        size="lg"
                        :fullWidth="true"
                        class="sm:fullWidth"
                    >
                        Close Without Saving
                    </x-buttons.submit-button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Save Confirmation Modal --}}
@if($showSaveConfirmation)
    <div class="fixed inset-0 z-60 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/80 transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
            <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all w-full max-w-md sm:max-w-lg">
                <div class="bg-white px-4 py-5 sm:px-6 sm:py-6">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full shrink-0">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="ml-3 sm:ml-4 text-lg font-semibold text-gray-900">Save Changes</h3>
                    </div>
                </div>

                <div class="px-4 py-3 sm:px-6 sm:py-4">
                    <p class="text-sm text-gray-600">
                        Are you sure you want to save these changes to this reason?
                    </p>
                </div>

                <div class="px-4 py-3 sm:px-6 sm:py-4 bg-gray-50 flex flex-col-reverse sm:flex-row justify-end gap-3 sm:gap-3">
                    <x-buttons.submit-button
                        wire:click="$set('showSaveConfirmation', false)"
                        color="gray"
                        size="lg"
                        :fullWidth="true"
                        class="sm:fullWidth"
                    >
                        Cancel
                    </x-buttons.submit-button>

                    <x-buttons.submit-button
                        wire:click="confirmSaveReasonEdit"
                        color="blue"
                        size="lg"
                        :fullWidth="true"
                        class="sm:fullWidth"
                        :disabled="$savingReason"
                    >
                        <svg wire:loading.remove wire:target="confirmSaveReasonEdit" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <svg wire:loading wire:target="confirmSaveReasonEdit" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="confirmSaveReasonEdit">Save Changes</span>
                        <span wire:loading wire:target="confirmSaveReasonEdit">Saving...</span>
                    </x-buttons.submit-button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Delete Confirmation Modal --}}
@if($this->showDeleteReasonConfirmation)
    <div class="fixed inset-0 z-60 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/80 transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
            <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all w-full max-w-md sm:max-w-lg">
                <div class="bg-white px-4 py-5 sm:px-6 sm:py-6">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-red-100 rounded-full shrink-0">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <h3 class="ml-3 sm:ml-4 text-lg font-semibold text-gray-900">Delete Reason</h3>
                    </div>
                </div>

                <div class="px-4 py-3 sm:px-6 sm:py-4">
                    <p class="text-sm text-gray-600">
                        Are you sure you want to delete this reason? Any disinfection slips using this reason will show "No Reason" instead.
                    </p>
                </div>

                <div class="px-4 py-3 sm:px-6 sm:py-4 bg-gray-50 flex flex-col-reverse sm:flex-row justify-end gap-3 sm:gap-3">
                    <x-buttons.submit-button
                        wire:click="$set('showDeleteReasonConfirmation', false)"
                        color="gray"
                        size="lg"
                        :fullWidth="true"
                        class="sm:fullWidth"
                    >
                        Cancel
                    </x-buttons.submit-button>

                    <x-buttons.submit-button
                        wire:click="deleteReason"
                        color="red"
                        size="lg"
                        :fullWidth="true"
                        class="sm:fullWidth"
                    >
                        Delete Reason
                    </x-buttons.submit-button>
                </div>
            </div>
        </div>
    </div>
@endif