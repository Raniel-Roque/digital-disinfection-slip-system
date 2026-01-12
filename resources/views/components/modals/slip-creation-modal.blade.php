@props([
    'trucks' => collect(),
    'locations' => collect(),
    'drivers' => collect(),
    'truckOptions' => [],
    'locationOptions' => [],
    'driverOptions' => [],
    'reasonOptions' => [],
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

        {{-- Reason --}}
        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
            <div class="font-semibold text-gray-500">Reason:<span class="text-red-500">*</span></div>
            <div class="text-gray-900">
                <x-forms.searchable-dropdown wire-model="reason_id" :options="$reasonOptions" search-property="searchReason"
                    placeholder="Select reason..." search-placeholder="Search reasons..." />
                @error('reason_id')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
        </div>

        {{-- Photos --}}
        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
            <div class="font-semibold text-gray-500">Photos:</div>
            <div class="text-gray-900" x-data="{
                showCameraModal: false,
                showCancelConfirmation: false,
                stream: null,
                photos: [],
                cameraActive: false,
                uploading: false,
                processingGallery: false,
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
                    
                    // Draw the video frame
                    ctx.drawImage(video, 0, 0);
                    
                    // Add timestamp watermark
                    this.addTimestampWatermark(ctx, canvas.width, canvas.height);
                    
                    const imageData = canvas.toDataURL('image/jpeg', 0.85);
                    this.photos.push({ id: Date.now(), data: imageData });
                    console.log('Photo captured! Total photos:', this.photos.length);
                },
                addTimestampWatermark(ctx, width, height) {
                    // Add timestamp overlay at bottom left
                    const now = new Date();
                    const dateStr = now.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: '2-digit', 
                        day: '2-digit' 
                    });
                    const timeStr = now.toLocaleTimeString('en-US', { 
                        hour: '2-digit', 
                        minute: '2-digit', 
                        second: '2-digit',
                        hour12: false 
                    });
                    const timestamp = `${dateStr} ${timeStr}`;
                    
                    // Configure text style
                    const fontSize = Math.max(16, height * 0.03); // Responsive font size
                    ctx.font = `bold ${fontSize}px Arial`;
                    ctx.textBaseline = 'bottom';
                    
                    // Add semi-transparent black background for text
                    const padding = fontSize * 0.3;
                    const textWidth = ctx.measureText(timestamp).width;
                    const textHeight = fontSize;
                    const bgX = padding;
                    const bgY = height - textHeight - padding * 2;
                    const bgWidth = textWidth + padding * 2;
                    const bgHeight = textHeight + padding * 2;
                    
                    ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
                    ctx.fillRect(bgX, bgY, bgWidth, bgHeight);
                    
                    // Draw text with black stroke/border for maximum visibility
                    const textX = padding * 2;
                    const textY = height - padding * 2;
                    
                    // Draw black stroke (border) around text
                    ctx.strokeStyle = '#000000';
                    ctx.lineWidth = fontSize * 0.15;
                    ctx.lineJoin = 'round';
                    ctx.miterLimit = 2;
                    ctx.strokeText(timestamp, textX, textY);
                    
                    // Draw white text on top
                    ctx.fillStyle = '#FFFFFF';
                    ctx.fillText(timestamp, textX, textY);
                },
                async selectFromGallery() {
                    console.log('Opening gallery...');
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.accept = 'image/*';
                    input.multiple = true;
                    
                    input.onchange = async (e) => {
                        const files = Array.from(e.target.files);
                        if (files.length === 0) return;
                        
                        this.processingGallery = true;
                        
                        for (const file of files) {
                            await this.processGalleryImage(file);
                        }
                        
                        this.processingGallery = false;
                    };
                    
                    input.click();
                },
                async processGalleryImage(file) {
                    return new Promise((resolve) => {
                        const reader = new FileReader();
                        
                        reader.onload = (e) => {
                            const img = new Image();
                            
                            img.onload = () => {
                                const canvas = this.$refs.canvas;
                                const ctx = canvas.getContext('2d');
                                
                                // Match camera capture size (640x640 or maintain aspect ratio)
                                const maxDimension = 640;
                                let targetWidth = img.width;
                                let targetHeight = img.height;
                                
                                // Scale down if image is larger than maxDimension
                                if (img.width > maxDimension || img.height > maxDimension) {
                                    const scale = Math.min(maxDimension / img.width, maxDimension / img.height);
                                    targetWidth = Math.floor(img.width * scale);
                                    targetHeight = Math.floor(img.height * scale);
                                }
                                
                                // Set canvas to target size
                                canvas.width = targetWidth;
                                canvas.height = targetHeight;
                                
                                // Draw the resized image
                                ctx.drawImage(img, 0, 0, targetWidth, targetHeight);
                                
                                // Add timestamp watermark
                                this.addTimestampWatermark(ctx, canvas.width, canvas.height);
                                
                                // Compress with same quality as camera capture
                                const imageData = canvas.toDataURL('image/jpeg', 0.85);
                                
                                // Calculate final size
                                const base64Length = imageData.length - 'data:image/jpeg;base64,'.length;
                                const sizeInBytes = (base64Length * 3) / 4;
                                const sizeInMB = (sizeInBytes / 1024 / 1024).toFixed(2);
                                
                                console.log('Gallery photo processed: ' + targetWidth + 'x' + targetHeight + ', Size: ' + sizeInMB + 'MB');
                                
                                // Check if still too large (shouldn't happen with 640px max)
                                if (sizeInBytes > 15 * 1024 * 1024) {
                                    $wire.dispatch('toast', { 
                                        message: 'Photo \'' + file.name + '\' is too large even after resizing', 
                                        type: 'error' 
                                    });
                                    resolve();
                                    return;
                                }
                                
                                this.photos.push({ id: Date.now(), data: imageData });
                                console.log('Gallery photo added! Total photos:', this.photos.length);
                                resolve();
                            };
                            
                            img.onerror = () => {
                                console.error('Failed to load image');
                                $wire.dispatch('toast', { 
                                    message: 'Failed to load image: ' + file.name, 
                                    type: 'error' 
                                });
                                resolve();
                            };
                            
                            img.src = e.target.result;
                        };
                        
                        reader.onerror = () => {
                            console.error('Failed to read file');
                            $wire.dispatch('toast', { 
                                message: 'Failed to read file: ' + file.name, 
                                type: 'error' 
                            });
                            resolve();
                        };
                        
                        reader.readAsDataURL(file);
                    });
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
                tryCancel() {
                    // If there are captured photos or camera is active, show confirmation
                    if (this.photos.length > 0 || this.cameraActive) {
                        this.showCancelConfirmation = true;
                    } else {
                        this.confirmCancel();
                    }
                },
                confirmCancel() {
                    console.log('Cancelling and resetting...');
                    // Stop camera if active
                    this.stopCamera();
                    // Clear all photos
                    this.photos = [];
                    // Reset states
                    this.showCancelConfirmation = false;
                    this.showCameraModal = false;
                    this.uploading = false;
                    this.processingGallery = false;
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

                {{-- Cancel Confirmation Modal --}}
                <div x-show="showCancelConfirmation"
                    x-cloak
                    class="fixed inset-0 z-60 overflow-y-auto"
                    style="display: none;">
                    <div class="fixed inset-0 bg-black/50"></div>
                    <div class="relative min-h-screen flex items-center justify-center p-4">
                        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                            <div class="text-center">
                                <div class="mx-auto mb-4 text-yellow-500 w-16 h-16">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Cancel Photo Capture?</h3>
                                <p class="text-gray-700 mb-4">Any captured photos that haven't been uploaded will be lost.</p>
                                <div class="flex gap-3 justify-center">
                                    <button @click="showCancelConfirmation = false"
                                            class="px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                        Continue Capturing
                                    </button>
                                    <button @click="confirmCancel()"
                                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                        Yes, Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($pendingCount > 0)
                    <div class="flex items-center gap-2">
                        <button wire:click="openPendingAttachmentModal(0)"
                            class="inline-flex items-center px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium rounded-lg transition-colors duration-150 cursor-pointer">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            See Photos ({{ $pendingCount }})
                        </button>
                        <button @click="showCameraModal = true"
                            class="inline-flex items-center px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors duration-150 cursor-pointer">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add More
                        </button>
                    </div>
                @else
                    <button @click="showCameraModal = true"
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
                    
                    <div class="fixed inset-0 bg-black/80" @click="tryCancel()"></div>
                    
                    <div class="relative min-h-screen flex items-center justify-center p-4">
                        <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full p-6">
                            
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Add Photos</h3>
                                <button @click="tryCancel()" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="flex flex-col items-center space-y-4">
                                {{-- Status --}}
                                <div class="w-full text-center py-2 px-4 rounded-lg font-medium text-sm"
                                    :class="uploading ? 'bg-blue-100 text-blue-700' : (processingGallery ? 'bg-purple-100 text-purple-700' : (cameraActive ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'))">
                                    <span x-text="uploading ? 'Uploading...' : (processingGallery ? 'Processing images...' : (cameraActive ? 'Camera is active' : 'Capture or select photos'))"></span>
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
                            {{-- Mobile Layout --}}
                            <div class="flex flex-col gap-2 mt-6 w-full md:hidden">
                                {{-- Select from Gallery Button --}}
                                <button @click="selectFromGallery()"
                                        x-show="!uploading && !processingGallery"
                                        class="w-full px-4 py-2 bg-purple-500 text-white rounded-md hover:bg-purple-600">
                                    Select from Gallery
                                </button>

                                {{-- Start Camera Button --}}
                                <button @click="startCamera()"
                                        x-show="!cameraActive && !uploading && !processingGallery"
                                        class="w-full px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                    Start Camera
                                </button>

                                {{-- Capture Photo Button --}}
                                <button @click="capturePhoto()"
                                        x-show="cameraActive && !uploading && !processingGallery"
                                        class="w-full px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">
                                    Capture Photo
                                </button>

                                {{-- Stop Camera Button --}}
                                <button @click="stopCamera()"
                                        x-show="cameraActive && !uploading && !processingGallery"
                                        class="w-full px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600">
                                    Stop Camera
                                </button>

                                {{-- Upload Photos Button --}}
                                <button @click="uploadPhotos()"
                                        x-show="photos.length > 0 && !uploading && !processingGallery"
                                        :disabled="uploading || processingGallery"
                                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span x-show="!uploading">Upload <span x-text="photos.length"></span> Photo<span x-show="photos.length > 1">s</span></span>
                                    <span x-show="uploading">Uploading...</span>
                                </button>

                                {{-- Cancel Button --}}
                                <button @click="tryCancel()"
                                        :disabled="uploading || processingGallery"
                                        class="w-full px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Cancel
                                </button>
                            </div>

                            {{-- Desktop Layout --}}
                            <div class="hidden md:flex justify-end gap-2 mt-6">
                                <button @click="tryCancel()"
                                        :disabled="uploading || processingGallery"
                                        class="px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Cancel
                                </button>

                                <button @click="selectFromGallery()"
                                        x-show="!uploading && !processingGallery"
                                        class="px-4 py-2 bg-purple-500 text-white rounded-md hover:bg-purple-600">
                                    Select from Gallery
                                </button>

                                <button @click="startCamera()"
                                        x-show="!cameraActive && !uploading && !processingGallery"
                                        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                    Start Camera
                                </button>

                                <button @click="capturePhoto()"
                                        x-show="cameraActive && !uploading && !processingGallery"
                                        class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">
                                    Capture Photo
                                </button>

                                <button @click="stopCamera()"
                                        x-show="cameraActive && !uploading && !processingGallery"
                                        class="px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600">
                                    Stop Camera
                                </button>

                                <button @click="uploadPhotos()"
                                        x-show="photos.length > 0 && !uploading && !processingGallery"
                                        :disabled="uploading || processingGallery"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span x-show="!uploading">Upload <span x-text="photos.length"></span> Photo<span x-show="photos.length > 1">s</span></span>
                                    <span x-show="uploading">Uploading...</span>
                                </button>
                            </div>

                            {{-- Cancel Confirmation Modal --}}
                            <div x-show="showCancelConfirmation"
                                x-cloak
                                class="fixed inset-0 z-60 overflow-y-auto"
                                style="display: none;">
                                <div class="fixed inset-0 bg-black/50"></div>
                                <div class="relative min-h-screen flex items-center justify-center p-4">
                                    <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                                        <div class="text-center">
                                            <div class="mx-auto mb-4 text-yellow-500 w-16 h-16">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                </svg>
                                            </div>
                                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Cancel Photo Capture?</h3>
                                            <p class="text-gray-700 mb-4">Any captured photos that haven't been uploaded will be lost.</p>
                                            <div class="flex gap-3 justify-center">
                                                <button @click="showCancelConfirmation = false"
                                                        class="px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                                    Continue Capturing
                                                </button>
                                                <button @click="confirmCancel()"
                                                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                                    Yes, Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>  {{-- Close: relative bg-white rounded-lg shadow-xl max-w-2xl w-full p-6 --}}
                    </div>  {{-- Close: relative min-h-screen flex items-center justify-center p-4 --}}
                </div>  {{-- Close: x-show="showCameraModal" camera modal container --}}
            </div>  {{-- Close: x-data Alpine.js container --}}
        </div>  {{-- Close: Photos field --}}

        {{-- Remarks for Disinfection --}}
        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
            <div class="font-semibold text-gray-500">Remarks:</div>
            <div class="text-gray-900">
                <textarea wire:model="remarks_for_disinfection"
                    class="w-full border rounded px-2 py-2 text-sm border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    rows="6" placeholder="Enter remarks..."></textarea>
                @error('remarks_for_disinfection')
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
            <span wire:loading.remove wire:target="createSlip">Create</span>
            <span wire:loading.inline-flex wire:target="createSlip" class="inline-flex items-center gap-2">
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
                    // Create initial map of attachment IDs to user IDs for Alpine.js
                    $attachmentsMap = $pendingAttachments->mapWithKeys(fn($a) => [$a->id => $a->user_id])->toArray();
                    // Create a key based on attachment IDs to force Alpine re-initialization when attachments change
                    $attachmentsKey = implode(',', $pendingAttachmentIds ?? []);
                @endphp
                <div wire:key="pending-attachments-{{ $attachmentsKey }}" x-data="{
                    initialAttachmentsMap: @js($attachmentsMap),
                    currentUserId: @js($currentUserId),
                    isAdminOrSuperAdmin: @js($isAdminOrSuperAdmin),
                    currentIndex: $wire.get('currentPendingAttachmentIndex'),
                    attachmentIds: $wire.get('pendingAttachmentIds') || [],
                    getCurrentAttachmentId() {
                        const index = this.currentIndex;
                        const ids = this.attachmentIds;
                        return (index !== null && index >= 0 && index < ids.length) ? ids[index] : null;
                    },
                    getAttachmentUserId(attachmentId) {
                        // First check initial map
                        if (this.initialAttachmentsMap[attachmentId]) {
                            return this.initialAttachmentsMap[attachmentId];
                        }
                        return null;
                    },
                    get canDelete() {
                        // Computed property that re-evaluates when currentIndex or attachmentIds change
                        const attachmentId = this.getCurrentAttachmentId();
                        if (!attachmentId) {
                            return false;
                        }
                        // Get user ID from map
                        const attachmentUserId = this.getAttachmentUserId(attachmentId);
                        if (attachmentUserId === null) {
                            return false;
                        }
                        // Check permissions
                        return this.isAdminOrSuperAdmin || attachmentUserId === this.currentUserId;
                    },
                    deleteCurrentPhoto() {
                        const attachmentId = this.getCurrentAttachmentId();
                        if (attachmentId) {
                            $wire.call('confirmRemovePendingAttachment', attachmentId);
                        }
                    }
                }" x-init="
                    // Watch and sync Livewire properties to Alpine reactive properties
                    $watch(() => $wire.currentPendingAttachmentIndex, (newIndex) => {
                        this.currentIndex = newIndex;
                    });
                    $watch(() => $wire.pendingAttachmentIds, (newIds) => {
                        this.attachmentIds = newIds || [];
                        // Update map when attachments are deleted
                        const currentIds = new Set(this.attachmentIds);
                        Object.keys(this.initialAttachmentsMap).forEach(id => {
                            if (!currentIds.has(Number(id))) {
                                delete this.initialAttachmentsMap[id];
                            }
                        });
                    });
                    // Initialize reactive properties
                    this.currentIndex = $wire.get('currentPendingAttachmentIndex');
                    this.attachmentIds = $wire.get('pendingAttachmentIds') || [];
                ">
                    <x-buttons.submit-button 
                        @click="deleteCurrentPhoto()"
                        color="red"
                        x-show="canDelete"
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
