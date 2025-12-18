@props(['show', 'selectedSlip' => null])

<x-modals.modal-template :show="$show" title="Attachments" max-width="max-w-4xl" backdrop-opacity="40">

    @php
        $attachments = $selectedSlip?->attachments() ?? collect([]);
        $totalAttachments = $attachments->count();
        $isReceivingGuard = \Illuminate\Support\Facades\Auth::id() === $selectedSlip?->received_guard_id;
        $status = $selectedSlip?->status ?? null;
        $currentUserId = \Illuminate\Support\Facades\Auth::id();
        $currentUser = \Illuminate\Support\Facades\Auth::user();
        $isAdminOrSuperAdmin = $currentUser && in_array($currentUser->user_type, [1, 2]); // 1 = Admin, 2 = SuperAdmin
    @endphp

    @if ($totalAttachments > 0)
        <div class="relative" x-data="{ currentIndex: @entangle('currentAttachmentIndex') }">
            {{-- Carousel Container --}}
            <div class="relative overflow-hidden rounded-lg bg-gray-100 min-h-[400px] flex items-center justify-center">
                {{-- Previous Button --}}
                @if ($totalAttachments > 1)
                    <button 
                        wire:click="previousAttachment"
                        x-show="currentIndex > 0"
                        class="absolute left-2 top-1/2 -translate-y-1/2 z-10 bg-white/90 hover:bg-white rounded-full p-2 shadow-lg transition-all"
                        :class="{ 'opacity-50 cursor-not-allowed': currentIndex === 0 }">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                @endif

                {{-- Images Container --}}
                <div class="flex transition-transform duration-300 ease-in-out w-full" 
                     :style="`transform: translateX(-${currentIndex * 100}%)`">
                    @foreach ($attachments as $index => $attachment)
                        @php
                            $fileUrl = Storage::url($attachment->file_path);
                            $extension = strtolower(pathinfo($attachment->file_path ?? '', PATHINFO_EXTENSION));
                            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            $isImage = in_array($extension, $imageExtensions);
                        @endphp
                        <div class="w-full shrink-0 px-4 py-4" style="min-width: 100%">
                            @if ($isImage)
                                <img src="{{ $fileUrl }}" 
                                     class="border shadow-md max-h-[60vh] max-w-full object-contain mx-auto rounded-lg"
                                     alt="Attachment {{ $index + 1 }}">
                            @else
                                <div class="text-center p-8">
                                    <p class="text-sm text-gray-600 mb-4">
                                        This file type cannot be previewed.
                                    </p>
                                    <a href="{{ $fileUrl }}" target="_blank" 
                                       class="text-orange-500 font-semibold underline hover:cursor-pointer cursor-pointer">
                                        Download attachment
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Next Button --}}
                @if ($totalAttachments > 1)
                    <button 
                        wire:click="nextAttachment"
                        x-show="currentIndex < {{ $totalAttachments - 1 }}"
                        class="absolute right-2 top-1/2 -translate-y-1/2 z-10 bg-white/90 hover:bg-white rounded-full p-2 shadow-lg transition-all"
                        :class="{ 'opacity-50 cursor-not-allowed': currentIndex >= {{ $totalAttachments - 1 }} }">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                @endif
            </div>

            {{-- Indicators/Dots --}}
            @if ($totalAttachments > 1)
                <div class="flex justify-center mt-4 space-x-2">
                    @foreach ($attachments as $index => $attachment)
                        <button 
                            wire:click="openAttachmentModal({{ $index }})"
                            class="w-2 h-2 rounded-full transition-all"
                            :class="currentIndex === {{ $index }} ? 'bg-orange-500 w-6' : 'bg-gray-300'"
                            x-bind:class="currentIndex === {{ $index }} ? 'bg-orange-500 w-6' : 'bg-gray-300'">
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Photo Counter --}}
            @if ($totalAttachments > 1)
                <div class="text-center mt-2 text-sm text-gray-600">
                    Photo <span x-text="currentIndex + 1"></span> of {{ $totalAttachments }}
                </div>
            @endif
        </div>
    @else
        <div class="text-center p-8 text-gray-500">
            No attachments available.
        </div>
    @endif

    @php
        // Check if user can manage attachments (for outgoing editing or incoming disinfecting)
        $canManage = false;
        if ($selectedSlip) {
            $isReceivingGuard = \Illuminate\Support\Facades\Auth::id() === $selectedSlip->received_guard_id;
            $isHatcheryGuard = \Illuminate\Support\Facades\Auth::id() === $selectedSlip->hatchery_guard_id;
            $currentLocationId = \Illuminate\Support\Facades\Session::get('location_id');
            
            // Can manage if receiving guard on incoming (status 1) OR hatchery guard on outgoing (status != 2)
            $canManage = ($isReceivingGuard && $status == 1 && $selectedSlip->destination_id === $currentLocationId) ||
                        ($isHatcheryGuard && $selectedSlip->location_id === $currentLocationId && $status != 2);
        }
    @endphp

    <x-slot name="footer">
        <div class="flex justify-between items-center w-full">
            {{-- Delete Current Photo Button (only if user can manage attachments AND (uploaded the current photo OR is admin/superadmin)) --}}
            @if ($canManage && $totalAttachments > 0)
                <div x-data="{ 
                    currentIndex: @entangle('currentAttachmentIndex'),
                    attachments: @js($attachments->map(fn($a) => ['id' => $a->id, 'user_id' => $a->user_id])->values()->all()),
                    currentUserId: @js($currentUserId),
                    isAdminOrSuperAdmin: @js($isAdminOrSuperAdmin)
                }">
                    <x-buttons.submit-button 
                        x-bind:wire:click="`confirmRemoveAttachment(${attachments[currentIndex]?.id})`"
                        color="red"
                        x-show="currentIndex < attachments.length && (isAdminOrSuperAdmin || attachments[currentIndex]?.user_id === currentUserId)">
                        Delete
                    </x-buttons.submit-button>
                </div>
            @elseif ($isAdminOrSuperAdmin && $totalAttachments > 0)
                {{-- Admin/SuperAdmin can delete even if canManage is false --}}
                <div x-data="{ 
                    currentIndex: @entangle('currentAttachmentIndex'),
                    attachments: @js($attachments->map(fn($a) => ['id' => $a->id, 'user_id' => $a->user_id])->values()->all())
                }">
                    <x-buttons.submit-button 
                        x-bind:wire:click="`confirmRemoveAttachment(${attachments[currentIndex]?.id})`"
                        color="red"
                        x-show="currentIndex < attachments.length">
                        Delete
                    </x-buttons.submit-button>
                </div>
            @else
                <div></div>
            @endif

            {{-- Back Button (always visible) --}}
            <x-buttons.submit-button wire:click="closeAttachmentModal" color="white">
                Back
            </x-buttons.submit-button>
        </div>
    </x-slot>

</x-modals.modal-template>

{{-- Remove Attachment Confirmation Modal --}}
<x-modals.delete-confirmation show="showRemoveAttachmentConfirmation" title="DELETE PHOTO?"
    message="Are you sure you want to delete this photo?" warning="This action cannot be undone."
    onConfirm="removeAttachment" confirmText="Yes, Delete Photo" cancelText="Cancel" />
