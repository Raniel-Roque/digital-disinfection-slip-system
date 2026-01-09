@props(['reasons' => null])

{{-- Reasons Settings Modal --}}
<x-modals.modal-template 
    show="showReasonsModal" 
    title="Reasons Settings" 
    maxWidth="max-w-3xl"
>
    <div class="space-y-4">
        {{-- Reasons List --}}
        <div class="space-y-2">
            @forelse($reasons as $index => $reason)
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    {{-- Editable Reason Text --}}
                    <div class="flex-1">
                        <input 
                            type="text" 
                            wire:model="reasonTexts.{{ $reason->id }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="Reason text"
                        >
                        @error('reasonTexts.' . $reason->id)
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    {{-- Delete Button --}}
                    <x-buttons.submit-button 
                        wire:click="confirmDeleteReason({{ $reason->id }})"
                        color="red" 
                        size="sm"
                        :fullWidth="false"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </x-buttons.submit-button>
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

        {{-- Add New Reason Button --}}
        <div class="pt-4 border-t border-gray-200">
            <x-buttons.submit-button 
                wire:click="addNewReason"
                color="blue" 
                size="default"
                :fullWidth="true"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add New Reason
            </x-buttons.submit-button>
        </div>
    </div>

    <x-slot name="footer">
        <x-buttons.submit-button 
            wire:click="closeReasonsModal"
            color="gray" 
            size="lg"
            :fullWidth="false"
        >
            Cancel
        </x-buttons.submit-button>
        
        <x-buttons.submit-button 
            wire:click="saveReasons" 
            color="blue" 
            size="lg"
            :fullWidth="false"
        >
            Save Changes
        </x-buttons.submit-button>
    </x-slot>
</x-modals.modal-template>

{{-- Delete Confirmation Modal --}}
@if($this->showDeleteReasonConfirmation)
    <div class="fixed inset-0 z-60 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/80 transition-opacity"></div>
        
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all w-full max-w-lg">
                <div class="bg-white px-6 py-4">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-red-100 rounded-full">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <h3 class="ml-4 text-lg font-semibold text-gray-900">Delete Reason</h3>
                    </div>
                </div>

                <div class="px-6 py-4">
                    <p class="text-sm text-gray-600">
                        Are you sure you want to delete this reason? Any disinfection slips using this reason will show "No Reason" instead.
                    </p>
                </div>

                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                    <x-buttons.submit-button 
                        wire:click="$set('showDeleteReasonConfirmation', false)"
                        color="gray" 
                        size="lg"
                        :fullWidth="false"
                    >
                        Cancel
                    </x-buttons.submit-button>
                    
                    <x-buttons.submit-button 
                        wire:click="deleteReason"
                        color="red" 
                        size="lg"
                        :fullWidth="false"
                    >
                        Delete Reason
                    </x-buttons.submit-button>
                </div>
            </div>
        </div>
    </div>
@endif