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
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs text-gray-600">{{ $pendingCount }} photo(s) attached</span>
                        <button @click="showCameraModal = true; startCamera()"
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
                <button @click="showCameraModal = true; startCamera()"
                    class="text-blue-500 hover:text-blue-600 underline cursor-pointer text-xs">
                    {{ $pendingCount > 0 ? 'Add More Attachment' : 'Add Attachment' }}
                </button>

                {{-- Camera Modal --}}
                <div x-show="showCameraModal" 
                    x-cloak
                    class="fixed inset-0 z-50 overflow-y-auto"
                    style="display: none;">
                    
                    <div class="fixed inset-0 bg-black/80" @click="showCameraModal = false; stopCamera()"></div>
                    
                    <div class="relative min-h-screen flex items-center justify-center p-4">
                        <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full p-6">
                            
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Add Attachment</h3>
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
