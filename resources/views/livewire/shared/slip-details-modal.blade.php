@if ($selectedSlip)
    @php
        $status = $selectedSlip?->status ?? null;
        // Status: 0 = Pending, 1 = Disinfecting, 2 = In-Transit, 3 = Completed, 4 = Incomplete
        
        // Header class based on status
        $headerClass = '';
        if ($status == 0) {
            $headerClass = 'border-t-4 border-t-gray-500 bg-gray-50';      // Pending - Neutral
        } elseif ($status == 1) {
            $headerClass = 'border-t-4 border-t-blue-500 bg-blue-50';     // Disinfecting - In Progress
        } elseif ($status == 2) {
            $headerClass = 'border-t-4 border-t-yellow-500 bg-yellow-50';  // In-Transit - Transit State
        } elseif ($status == 3) {
            $headerClass = 'border-t-4 border-t-green-500 bg-green-50';    // Completed - Success
        } elseif ($status == 4) {
            $headerClass = 'border-t-4 border-t-red-500 bg-red-50';        // Incomplete - Issue State
        }
    @endphp
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
                        <div class="text-gray-900">{{ $selectedSlip->created_at->format('M d, Y - h:i A') }}</div>
                </div>
                    <div>
                        <div class="font-semibold text-gray-500 mb-0.5">Slip No:</div>
                        <div class="text-gray-900 font-semibold">{{ $selectedSlip->slip_id }}</div>
            </div>
                </div>
            </div>

            {{-- Body Fields --}}
            @php
                // Track row index for alternating colors
                $rowIndex = 0;
            @endphp
            <div class="space-y-0 -mx-6">
                {{-- Plate No --}}
                @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                    <div class="font-semibold text-gray-500">Plate No:</div>
                    <div class="text-gray-900">
                        @if ($selectedSlip->vehicle)
                            {{ $selectedSlip->vehicle->vehicle }}
                            @if ($selectedSlip->vehicle->trashed())
                                <span class="text-red-600 font-semibold">(Deleted)</span>
                            @endif
                        @else
                            <span class="text-red-600 font-semibold">(Deleted)</span>
                        @endif
                    </div>
                </div>

            {{-- Driver --}}
                @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                    <div class="font-semibold text-gray-500">Driver:</div>
                    <div class="text-gray-900">
                        {{ $selectedSlip->driver?->first_name . ' ' . $selectedSlip->driver?->last_name ?? 'N/A' }}
                    </div>
                </div>

                {{-- Origin --}}
                @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                    <div class="font-semibold text-gray-500">Origin:</div>
                    <div class="text-gray-900">
                        {{ $selectedSlip->location->location_name ?? 'N/A' }}
            </div>
                </div>

                {{-- Destination --}}
                @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                    <div class="font-semibold text-gray-500">Destination:</div>
                    <div class="text-gray-900">
                        {{ $selectedSlip->destination->location_name ?? 'N/A' }}
                    </div>
                </div>

                {{-- Completion Date (only when completed) --}}
                @if ($status == 2 && $selectedSlip->completed_at)
                    @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
                    <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                        <div class="font-semibold text-gray-500">End Date:</div>
                        <div class="text-gray-900">
                            {{ \Carbon\Carbon::parse($selectedSlip->completed_at)->format('M d, Y - h:i A') }}
                        </div>
                    </div>
                @endif

                {{-- Photos --}}
                @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                    <div class="font-semibold text-gray-500">Photos:</div>
                    <div class="text-gray-900">
                        @if ($this->selectedSlipAttachments->count() > 0)
                            <button wire:click="openAttachmentModal(0)"
                                class="inline-flex items-center px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium rounded-lg transition-colors duration-150 cursor-pointer">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                See Photo{{ $this->selectedSlipAttachments->count() > 1 ? 's (' . $this->selectedSlipAttachments->count() . ')' : '' }}
                            </button>
                        @else
                            N/A
                        @endif
                    </div>
                </div>

                {{-- Reason --}}
                @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                    <div class="font-semibold text-gray-500">Reason:</div>
                    <div class="text-gray-900">
                        {{ $this->displayReason }}
                    </div>
                </div>

                {{-- Remarks --}}
                @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                    <div class="font-semibold text-gray-500">Remarks:</div>
                    <div class="text-gray-900 wrap-break-words min-w-0" style="word-break: break-word; overflow-wrap: break-word;">
                        <div class="whitespace-pre-wrap">{{ $selectedSlip->remarks_for_disinfection ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            {{-- Sub Footer --}}
            <div class="border-t border-gray-200 px-6 py-2 bg-gray-50 -mx-6 -mb-6 mt-2">
                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <div class="font-semibold text-gray-500 mb-0.5">Hatchery Guard:</div>
                        <div>
                            @if ($selectedSlip->hatcheryGuard)
                                <span class="text-gray-900">{{ $selectedSlip->hatcheryGuard->first_name . ' ' . $selectedSlip->hatcheryGuard->last_name }}@if(isset($selectedSlip->hatcheryGuard->username)) &#64;{{ $selectedSlip->hatcheryGuard->username }}@endif</span>
                                @if ($selectedSlip->hatcheryGuard->trashed())
                                    <span class="text-red-600 font-semibold">(Deleted)</span>
                                @endif
                            @else
                                <span class="text-gray-900">N/A</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-500 mb-0.5">Received By:</div>
                        <div>
                            @if ($selectedSlip->receivedGuard)
                                <span class="text-gray-900">{{ $selectedSlip->receivedGuard->first_name . ' ' . $selectedSlip->receivedGuard->last_name }}@if(isset($selectedSlip->receivedGuard->username)) &#64;{{ $selectedSlip->receivedGuard->username }}@endif</span>
                                @if ($selectedSlip->receivedGuard->trashed())
                                    <span class="text-red-600 font-semibold">(Deleted)</span>
                                @endif
                            @else
                                <span class="text-gray-900">N/A</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @else
            <p class="text-gray-500 text-center">No details available.</p>
        @endif

        {{-- Footer --}}
        <x-slot name="footer">
            {{-- Mobile Layout --}}
            <div class="flex flex-col gap-2 w-full md:hidden">
                @if ($this->canEdit())
                    <div class="grid grid-cols-2 gap-2 w-full">
                        <x-buttons.submit-button wire:click="openEditModal" color="orange" class="w-full">
                            Edit
                        </x-buttons.submit-button>
                        <x-buttons.submit-button wire:click="closeDetailsModal" color="white" class="w-full">
                            Close
                        </x-buttons.submit-button>
                    </div>
                @else
                    <x-buttons.submit-button wire:click="closeDetailsModal" color="white" class="w-full">
                        Close
                    </x-buttons.submit-button>
                @endif
            </div>

            {{-- Desktop Layout --}}
            <div class="hidden md:flex justify-end w-full gap-2">
                <x-buttons.submit-button wire:click="closeDetailsModal" color="white">
                    Close
                </x-buttons.submit-button>

                {{-- Edit Button (Only for non-completed/incomplete slips) --}}
                @if ($this->canEdit())
                    <x-buttons.submit-button wire:click="openEditModal" color="orange">
                        Edit
                    </x-buttons.submit-button>
                @endif
            </div>
        </x-slot>

    </x-modals.modal-template>

    {{-- Photos Carousel Modal --}}
    @if ($showAttachmentModal && $selectedSlip)
        @php
            $photos = $this->selectedSlipAttachments;
            $totalAttachments = $photos->count();
            $isReceivingGuard = \Illuminate\Support\Facades\Auth::id() === $selectedSlip?->received_guard_id;
            $status = $selectedSlip?->status ?? null;
            $currentUserId = \Illuminate\Support\Facades\Auth::id();
            $currentUser = \Illuminate\Support\Facades\Auth::user();
            $currentRoute = \Illuminate\Support\Facades\Request::path();
            
            // Check if user has admin/superadmin privileges in current context
            // On /user routes (guards), even superadmins should only have guard privileges
            $isOnUserRoute = str_starts_with($currentRoute, 'user');
            $isAdminOrSuperAdmin = !$isOnUserRoute && $currentUser && in_array($currentUser->user_type, [1, 2]); // 1 = Admin, 2 = SuperAdmin
        @endphp

        <x-modals.modal-template show="showAttachmentModal" title="Photos" max-width="w-[96%] sm:max-w-4xl" backdrop-opacity="40">
            @if ($totalAttachments > 0)
                <div class="relative" x-data="{ currentIndex: @entangle('currentAttachmentIndex').live }">
                    {{-- Carousel Container --}}
                    <div class="relative overflow-hidden rounded-lg bg-gray-100 min-h-[300px] sm:min-h-[400px] flex items-center justify-center">
                        {{-- Previous Button --}}
                        @if ($totalAttachments > 1)
                            <button 
                                @click="$wire.previousAttachment()"
                                x-show="currentIndex > 0"
                                class="absolute left-1 sm:left-2 top-1/2 -translate-y-1/2 z-10 bg-white/90 hover:bg-white rounded-full p-1.5 sm:p-2 shadow-lg transition-all mouse-pointer"
                                :class="{ 'opacity-50 cursor-not-allowed': currentIndex === 0 }">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                        @endif

                        {{-- Images Container --}}
                        <div class="flex transition-transform duration-300 ease-in-out w-full" 
                             :style="`transform: translateX(-${currentIndex * 100}%)`">
                            @foreach ($photos as $index => $Photo)
                                @php
                                    $fileUrl = Storage::url($Photo->file_path);
                                    $extension = strtolower(pathinfo($Photo->file_path ?? '', PATHINFO_EXTENSION));
                                    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                                    $isImage = in_array($extension, $imageExtensions);
                                    $uploader = $Photo->user;
                                    if ($uploader) {
                                        $uploaderName = trim($uploader->first_name . ' ' . ($uploader->middle_name ?? '') . ' ' . $uploader->last_name);
                                        $uploaderUsername = $uploader->username;
                                        $isUploaderDeleted = method_exists($uploader, 'trashed') && $uploader->trashed();
                                    } else {
                                        $uploaderName = null;
                                        $uploaderUsername = null;
                                        $isUploaderDeleted = false;
                                    }
                                @endphp
                                <div class="w-full shrink-0 px-2 sm:px-4 py-2 sm:py-4 flex flex-col" style="min-width: 100%">
                                    @if ($isImage)
                                        <img src="{{ $fileUrl }}" 
                                             class="border shadow-md max-h-[45vh] sm:max-h-[55vh] max-w-full w-auto object-contain mx-auto rounded-lg"
                                             alt="Photo {{ $index + 1 }}">
                                        {{-- Uploaded By Information --}}
                                        <div class="text-center mt-2 sm:mt-3 text-xs sm:text-sm text-gray-600">
                                            <span class="font-semibold">Uploaded by:</span> 
                                            @if($uploaderName && $uploaderUsername)
                                                <span class="text-gray-800">{{ $uploaderName }}</span>
                                                <span class="text-gray-500">&#64;{{ $uploaderUsername }}</span>
                                                @if($isUploaderDeleted)
                                                    <span class="text-red-600 font-semibold"> (Deleted)</span>
                                                @endif
                                            @elseif($uploaderName)
                                                <span class="text-gray-800">{{ $uploaderName }}</span>
                                                @if($isUploaderDeleted)
                                                    <span class="text-red-600 font-semibold"> (Deleted)</span>
                                                @endif
                                            @else
                                                <span class="text-gray-800">(Deleted)</span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="text-center p-4 sm:p-8">
                                            <p class="text-xs sm:text-sm text-gray-600 mb-2 sm:mb-4">
                                                This file type cannot be previewed.
                                            </p>
                                            <a href="{{ $fileUrl }}" target="_blank" 
                                               class="text-orange-500 font-semibold underline hover:cursor-pointer cursor-pointer text-sm sm:text-base">
                                                Download photo
                                            </a>
                                            {{-- Uploaded By Information for non-images --}}
                                            <div class="mt-3 sm:mt-4 text-xs sm:text-sm text-gray-600">
                                                <span class="font-semibold">Uploaded by:</span> 
                                                @if($uploaderName && $uploaderUsername)
                                                    <span class="text-gray-800">{{ $uploaderName }}</span>
                                                    <span class="text-gray-500">&#64;{{ $uploaderUsername }}</span>
                                                    @if($isUploaderDeleted)
                                                        <span class="text-red-600 font-semibold"> (Deleted)</span>
                                                    @endif
                                                @elseif($uploaderName)
                                                    <span class="text-gray-800">{{ $uploaderName }}</span>
                                                    @if($isUploaderDeleted)
                                                        <span class="text-red-600 font-semibold"> (Deleted)</span>
                                                    @endif
                                                @else
                                                    <span class="text-gray-800">(Deleted)</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Next Button --}}
                        @if ($totalAttachments > 1)
                            <button 
                                @click="$wire.nextAttachment()"
                                x-show="currentIndex < {{ $totalAttachments - 1 }}"
                                class="absolute right-1 sm:right-2 top-1/2 -translate-y-1/2 z-10 bg-white/90 hover:bg-white rounded-full p-1.5 sm:p-2 shadow-lg transition-all mouse-pointer"
                                :class="{ 'opacity-50 cursor-not-allowed': currentIndex >= {{ $totalAttachments - 1 }} }">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        @endif
                    </div>

                    {{-- Indicators/Dots --}}
                    @if ($totalAttachments > 1)
                        <div class="flex justify-center mt-3 sm:mt-4 space-x-1.5 sm:space-x-2 overflow-x-auto max-w-full px-2">
                            @foreach ($photos as $index => $Photo)
                                <button 
                                    @click="$wire.openAttachmentModal({{ $index }})"
                                    class="w-2 h-2 rounded-full transition-all shrink-0"
                                    :class="currentIndex === {{ $index }} ? 'bg-orange-500 w-4 sm:w-6' : 'bg-gray-300'">
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Photo Counter --}}
                    @if ($totalAttachments > 1)
                        <div class="text-center mt-2 text-xs sm:text-sm text-gray-600">
                            Photo <span x-text="currentIndex + 1"></span> of {{ $totalAttachments }}
                        </div>
                    @endif
                </div>
            @else
                <div class="text-center p-8 text-gray-500">
                    No photos available.
                </div>
            @endif

            @php
                // Check if user can manage photos (for outgoing editing or incoming disinfecting)
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
                <div class="flex justify-between items-center w-full flex-wrap gap-2">
                    {{-- Delete Current Photo Button --}}
                    @if ($totalAttachments > 0)
                        @php
                            $attachmentsData = $photos->map(fn($a) => ['id' => $a->id, 'user_id' => $a->user_id])->values()->all();
                        @endphp
                        <div x-data="{
                            photos: @js($attachmentsData),
                            currentUserId: @js($currentUserId),
                            isAdminOrSuperAdmin: @js($isAdminOrSuperAdmin),
                            canManage: @js($canManage),
                            status: @js($status),
                            userType: @js($this->userType),
                            getCurrentAttachment() {
                                const index = $wire.get('currentAttachmentIndex');
                                return this.photos[index] || null;
                            },
                            canShowDelete() {
                                const Photo = this.getCurrentAttachment();
                                if (!Photo) return false;

                                // SuperAdmin can always delete regardless of status or ownership
                                if (this.userType === 2) return true;

                                // Admin cannot delete from completed/incomplete slips
                                if (this.userType === 1 && (this.status === 3 || this.status === 4)) return false;

                                // Admin can delete from pending/disinfecting/in-transit slips if they can manage
                                if (this.userType === 1 && this.canManage) return true;

                                return false;
                            },
                            deleteCurrentPhoto() {
                                const Photo = this.getCurrentAttachment();
                                if (Photo) {
                                    $wire.call('confirmRemoveAttachment', Photo.id);
                                }
                            }
                        }" x-init="$watch(() => $wire.currentAttachmentIndex, () => {})">
                            <x-buttons.submit-button 
                                @click="deleteCurrentPhoto()"
                                color="red"
                                x-show="canShowDelete()"
                                class="transition-all"
                                x-show="canShowDelete()">
                                Delete
                            </x-buttons.submit-button>
                        </div>
                    @else
                        <div></div>
                    @endif

                    {{-- Back Button (always visible) --}}
                    <x-buttons.submit-button @click="$wire.closeAttachmentModal()" color="white">
                        Back
                    </x-buttons.submit-button>
                </div>
            </x-slot>
        </x-modals.modal-template>
    @endif

    {{-- Remove Photo Confirmation Modal --}}
    <x-modals.confirmation-modal show="showRemoveAttachmentConfirmation" title="DELETE PHOTO?"
        message="Are you sure you want to delete this photo?" warning="This action cannot be undone."
        onConfirm="removeAttachment" confirmText="Yes, Delete Photo" cancelText="Cancel" />
@endif
