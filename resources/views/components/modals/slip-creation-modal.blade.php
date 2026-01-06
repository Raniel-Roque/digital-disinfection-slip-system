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

        {{-- Photos --}}
        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
            <div class="font-semibold text-gray-500">Photos:</div>
            <div class="text-gray-900" x-data="{ 
                showCameraModal: false,
                stream: null,
                photos: [],
                cameraActive: false,
                uploading: false,
                async startCamera() {
                    console.log('Starting camera...');
                    
                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        alert('Camera not supported! You need HTTPS or localhost.');
                        return;
                    }
                    
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({ 
                            video: { facingMode: 'environment', width: { ideal: 640 }, height: { ideal: 640 } },
                            audio: false 
                        });
                        this.$refs.video.srcObject = this.stream;
                        this.cameraActive = true;
                        console.log('Camera started!');
                    } catch(err) {
                        console.error('Camera error:', err);
                        alert('Camera error: ' + err.message);
                    }
                },
                capturePhoto() {
                    console.log('Capturing photo...');
                    const video = this.$refs.video;
                    const canvas = this.$refs.canvas;
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0);
                    const imageData = canvas.toDataURL('image/jpeg', 0.85);
                    this.photos.push({ id: Date.now(), data: imageData });
                    console.log('Photo captured! Total photos:', this.photos.length);
                },
                stopCamera() {
                    console.log('Stopping camera...');
                    if (this.stream) {
                        this.stream.getTracks().forEach(track => track.stop());
                        this.stream = null;
                    }
                    this.$refs.video.srcObject = null;
                    this.cameraActive = false;
                },
                deletePhoto(id) {
                    console.log('Deleting photo:', id);
                    this.photos = this.photos.filter(p => p.id !== id);
                },
                async uploadPhotos() {
                    if (this.photos.length === 0) {
                        alert('No photos to upload!');
                        return;
                    }
                    
                    console.log('Uploading photos...');
                    this.uploading = true;
                    
                    try {
                        // Upload all photos sequentially
                        for (const photo of this.photos) {
                            await $wire.uploadAttachment(photo.data);
                        }
                        
                        // Reset on success
                        this.photos = [];
                        this.stopCamera();
                        this.showCameraModal = false;
                        
                        console.log('Upload complete!');
                    } catch(err) {
                        console.error('Upload error:', err);
                        alert('Upload failed: ' + err.message);
                    } finally {
                        this.uploading = false;
                    }
                }
            }">
                @php
                    $pendingCount = count($pendingAttachmentIds ?? []);
                @endphp
                @if ($pendingCount > 0)
                    <div class="flex items-center gap-2">
                        <button wire:click="openPendingAttachmentModal(0)"
                            class="inline-flex items-center px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium rounded-lg transition-colors duration-150 cursor-pointer">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            See Photos ({{ $pendingCount }})
                        </button>
                        <button @click="showCameraModal = true; startCamera()"
                            class="inline-flex items-center px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors duration-150 cursor-pointer">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add More
                        </button>
                    </div>
                @else
                    <button @click="showCameraModal = true; startCamera()"
                        class="inline-flex items-center px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors duration-150 cursor-pointer">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Photos
                    </button>
                @endif

                {{-- Camera Modal --}}
                <div x-show="showCameraModal" 
                    x-cloak
                    class="fixed inset-0 z-50 overflow-y-auto"
                    style="display: none;">
                    
                    <div class="fixed inset-0 bg-black/80" @click="showCameraModal = false; stopCamera()"></div>
                    
                    <div class="relative min-h-screen flex items-center justify-center p-4">
                        <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full p-6">
                            
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Add Photos</h3>
                                <button @click="showCameraModal = false; stopCamera()" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="flex flex-col items-center space-y-4">
                                {{-- Status --}}
                                <div class="w-full text-center py-2 px-4 rounded-lg font-medium text-sm"
                                    :class="uploading ? 'bg-blue-100 text-blue-700' : (cameraActive ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700')">
                                    <span x-text="uploading ? 'Uploading...' : (cameraActive ? 'Camera is active' : 'Click Start Camera to begin')"></span>
                                </div>
                                
                                {{-- Camera Preview --}}
                                <div class="relative w-80 h-80 bg-gray-900 rounded-lg overflow-hidden"
                                    x-show="cameraActive">
                                    <video x-ref="video" class="w-full h-full object-cover" autoplay playsinline></video>
                                    <canvas x-ref="canvas" class="hidden"></canvas>
                                </div>

                                {{-- Photos Grid --}}
                                <div class="w-full" x-show="photos.length > 0">
                                    <h4 class="text-lg font-semibold text-gray-700 mb-3">Captured Photos (<span x-text="photos.length"></span>)</h4>
                                    <div class="grid grid-cols-3 gap-3">
                                        <template x-for="photo in photos" :key="photo.id">
                                            <div class="relative rounded-lg overflow-hidden shadow-md">
                                                <img :src="photo.data" class="w-full h-24 object-cover">
                                                <button @click="deletePhoto(photo.id)" 
                                                        class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white text-xs px-2 py-1 rounded">
                                                    Delete
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Footer Buttons --}}
                            <div class="flex justify-end gap-2 mt-6">
                                <button @click="showCameraModal = false; stopCamera()" 
                                        :disabled="uploading"
                                        class="px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Cancel
                                </button>
                                
                                <button @click="startCamera()" 
                                        x-show="!cameraActive && !uploading"
                                        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                    Start Camera
                                </button>
                                
                                <button @click="capturePhoto()" 
                                        x-show="cameraActive && !uploading"
                                        class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">
                                    Capture Photo
                                </button>
                                
                                <button @click="stopCamera()" 
                                        x-show="cameraActive && !uploading"
                                        class="px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600">
                                    Stop Camera
                                </button>
                                
                                <button @click="uploadPhotos()" 
                                        x-show="photos.length > 0 && !uploading"
                                        :disabled="uploading"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span x-show="!uploading">Upload <span x-text="photos.length"></span> Photo<span x-show="photos.length > 1">s</span></span>
                                    <span x-show="uploading">Uploading...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
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
        <div x-data="{ isCreating: @js($isCreating) }" class="flex flex-col sm:flex-row justify-end gap-2 w-full sm:w-auto -mt-8">
        <x-buttons.submit-button wire:click.prevent="createSlip" color="blue" wire:loading.attr="disabled" wire:target="createSlip"
                x-bind:disabled="isCreating">
            <span wire:loading.remove wire:target="createSlip">Create Slip</span>
            <span wire:loading wire:target="createSlip" class="inline-flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Creating...
            </span>
        </x-buttons.submit-button>

        <x-buttons.submit-button wire:click="closeCreateModal" color="white" wire:loading.attr="disabled" wire:target="createSlip">
            Cancel
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

{{-- Pending Photos Carousel Modal --}}
<x-modals.modal-template show="showPendingAttachmentModal" title="Photos" max-width="w-[96%] sm:max-w-4xl" backdrop-opacity="40">
    @php
        $pendingAttachments = collect($pendingAttachmentIds ?? [])->map(function($id) {
            return \App\Models\Attachment::find($id);
        })->filter();
        $totalPendingAttachments = $pendingAttachments->count();
    @endphp

    @if ($totalPendingAttachments > 0)
        <div class="relative" x-data="{ currentIndex: @entangle('currentPendingAttachmentIndex').live }">
            {{-- Carousel Container --}}
            <div class="relative overflow-hidden rounded-lg bg-gray-100 min-h-[300px] sm:min-h-[400px] flex items-center justify-center">
                {{-- Previous Button --}}
                @if ($totalPendingAttachments > 1)
                    <button 
                        @click="$wire.previousPendingAttachment()"
                        x-show="currentIndex > 0"
                        class="absolute left-1 sm:left-2 top-1/2 -translate-y-1/2 z-10 bg-white/90 hover:bg-white rounded-full p-1.5 sm:p-2 shadow-lg transition-all">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                @endif

                {{-- Images Container --}}
                <div class="flex transition-transform duration-300 ease-in-out w-full" 
                     :style="`transform: translateX(-${currentIndex * 100}%)`">
                    @foreach ($pendingAttachments as $index => $attachment)
                        @php
                            $fileUrl = \Illuminate\Support\Facades\Storage::url($attachment->file_path);
                            $extension = strtolower(pathinfo($attachment->file_path ?? '', PATHINFO_EXTENSION));
                            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            $isImage = in_array($extension, $imageExtensions);
                        @endphp
                        <div class="w-full shrink-0 px-2 sm:px-4 py-2 sm:py-4 flex flex-col" style="min-width: 100%">
                            @if ($isImage)
                                <img src="{{ $fileUrl }}" 
                                     class="border shadow-md max-h-[45vh] sm:max-h-[55vh] max-w-full w-auto object-contain mx-auto rounded-lg"
                                     alt="Attachment {{ $index + 1 }}">
                            @else
                                <div class="text-center p-4 sm:p-8">
                                    <p class="text-xs sm:text-sm text-gray-600 mb-2 sm:mb-4">
                                        This file type cannot be previewed.
                                    </p>
                                    <a href="{{ $fileUrl }}" target="_blank" 
                                       class="text-orange-500 font-semibold underline hover:cursor-pointer cursor-pointer text-sm sm:text-base">
                                        Download photo
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Next Button --}}
                @if ($totalPendingAttachments > 1)
                    <button 
                        @click="$wire.nextPendingAttachment()"
                        x-show="currentIndex < {{ $totalPendingAttachments - 1 }}"
                        class="absolute right-1 sm:right-2 top-1/2 -translate-y-1/2 z-10 bg-white/90 hover:bg-white rounded-full p-1.5 sm:p-2 shadow-lg transition-all">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                @endif
            </div>

            {{-- Indicators/Dots --}}
            @if ($totalPendingAttachments > 1)
                <div class="flex justify-center mt-3 sm:mt-4 space-x-1.5 sm:space-x-2 overflow-x-auto max-w-full px-2">
                    @foreach ($pendingAttachments as $index => $attachment)
                        <button 
                            @click="$wire.openPendingAttachmentModal({{ $index }})"
                            class="w-2 h-2 rounded-full transition-all shrink-0"
                            :class="currentIndex === {{ $index }} ? 'bg-orange-500 w-4 sm:w-6' : 'bg-gray-300'">
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Photo Counter --}}
            @if ($totalPendingAttachments > 1)
                <div class="text-center mt-2 text-xs sm:text-sm text-gray-600">
                    Photo <span x-text="currentIndex + 1"></span> of {{ $totalPendingAttachments }}
                </div>
            @endif
        </div>
    @else
        <div class="text-center p-8 text-gray-500">
            No photos available.
        </div>
    @endif

    @php
        $currentUser = \Illuminate\Support\Facades\Auth::user();
        $currentRoute = \Illuminate\Support\Facades\Request::path();
        $isOnUserRoute = str_starts_with($currentRoute, 'user');
        $isAdminOrSuperAdmin = !$isOnUserRoute && $currentUser && in_array($currentUser->user_type, [1, 2]);
        $currentUserId = \Illuminate\Support\Facades\Auth::id();
    @endphp

    <x-slot name="footer">
        <div class="flex justify-between items-center w-full flex-wrap gap-2">
            {{-- Delete Current Photo Button --}}
            @if ($totalPendingAttachments > 0)
                @php
                    $attachmentsData = $pendingAttachments->map(fn($a) => ['id' => $a->id, 'user_id' => $a->user_id])->values()->all();
                @endphp
                <div x-data="{
                    attachments: @js($attachmentsData),
                    currentUserId: @js($currentUserId),
                    isAdminOrSuperAdmin: @js($isAdminOrSuperAdmin),
                    getCurrentAttachment() {
                        const index = $wire.get('currentPendingAttachmentIndex');
                        return this.attachments[index] || null;
                    },
                    canShowDelete() {
                        const attachment = this.getCurrentAttachment();
                        return attachment && (this.isAdminOrSuperAdmin || attachment.user_id === this.currentUserId);
                    },
                    deleteCurrentPhoto() {
                        const attachment = this.getCurrentAttachment();
                        if (attachment) {
                            $wire.call('confirmRemovePendingAttachment', attachment.id);
                        }
                    }
                }" x-init="$watch(() => $wire.currentPendingAttachmentIndex, () => {})">
                    <x-buttons.submit-button 
                        @click="deleteCurrentPhoto()"
                        color="red"
                        x-show="canShowDelete()"
                        class="transition-all">
                        Delete
                    </x-buttons.submit-button>
                </div>
            @else
                <div></div>
            @endif

            {{-- Back Button --}}
            <x-buttons.submit-button @click="$wire.closePendingAttachmentModal()" color="white">
                Back
            </x-buttons.submit-button>
        </div>
    </x-slot>
</x-modals.modal-template>
