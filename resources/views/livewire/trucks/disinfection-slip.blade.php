@php
    use Illuminate\Support\Facades\Auth;
    $isHatcheryAssigned = Auth::id() === $selectedSlip?->hatchery_guard_id;
    $isReceivingGuard = Auth::id() === $selectedSlip?->received_guard_id;
    $status = $selectedSlip?->status ?? null;
    // Status: 0 = Pending, 1 = Disinfecting, 2 = In-Transit, 3 = Completed, 4 = Incomplete
    
    // Header class based on status
    $headerClass = '';
    if ($status == 0) {
        $headerClass = 'border-t-4 border-t-gray-500 bg-gray-50';
    } elseif ($status == 1) {
        $headerClass = 'border-t-4 border-t-orange-500 bg-orange-50';
    } elseif ($status == 2) {
        $headerClass = 'border-t-4 border-t-yellow-500 bg-yellow-50';
    } elseif ($status == 3) {
        $headerClass = 'border-t-4 border-t-green-500 bg-green-50';
    } elseif ($status == 4) {
        $headerClass = 'border-t-4 border-t-red-500 bg-red-50';
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
                        <div class="text-gray-900">{{ $selectedSlip->created_at->format('M d, Y - h:i A') }}</div>
                </div>
                    <div>
                        <div class="font-semibold text-gray-500 mb-0.5">Slip No:</div>
                        <div class="text-gray-900 font-semibold">{{ $selectedSlip->slip_id }}</div>
            </div>
                    <div class="flex items-center">
                        <button wire:click="openIssueModal" type="button"
                            class="p-1.5 text-red-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 cursor-pointer"
                            title="Report Issue">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                            </svg>
                        </button>
            </div>
                </div>
            </div>

            {{-- Body Fields --}}
            @php
                // Track row index for alternating colors
                $rowIndex = 0;
            @endphp
            <div class="space-y-0 -mx-6">
                {{-- Vehicle --}}
                @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                    <div class="font-semibold text-gray-500">Vehicle:</div>
                    <div class="text-gray-900">
                    @if ($isEditing)
                        <x-forms.searchable-dropdown-paginated wire-model="truck_id" data-method="getPaginatedTrucks"
                            search-property="searchTruck" placeholder="Select vehicle..."
                            search-placeholder="Search vehicles..." :per-page="20" />
                        @error('truck_id')
                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    @else
                        {{ $selectedSlip->truck->plate_number ?? 'N/A' }}
                    @endif
                </div>
            </div>

                {{-- Driver --}}
                @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                    <div class="font-semibold text-gray-500">Driver:</div>
                    <div class="text-gray-900">
                    @if ($isEditing)
                        <x-forms.searchable-dropdown-paginated wire-model="driver_id" data-method="getPaginatedDrivers"
                            search-property="searchDriver" placeholder="Select driver..."
                            search-placeholder="Search drivers..." :per-page="20" />
                        @error('driver_id')
                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    @else
                        {{ $selectedSlip->driver?->first_name . ' ' . $selectedSlip->driver?->last_name ?? 'N/A' }}
                    @endif
                </div>
            </div>

                {{-- Origin --}}
                @if (!$isEditing)
                    @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
                    <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                        <div class="font-semibold text-gray-500">Origin:</div>
                        <div class="text-gray-900">
                            {{ $selectedSlip->location->location_name ?? 'N/A' }}
            </div>
                    </div>
                @endif

                {{-- Destination --}}
                @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                    <div class="font-semibold text-gray-500">Destination:</div>
                    <div class="text-gray-900">
                        @if ($isEditing)
                            <x-forms.searchable-dropdown-paginated wire-model="destination_id" data-method="getPaginatedLocations"
                                search-property="searchDestination" placeholder="Select destination..."
                                search-placeholder="Search locations..." :per-page="20" />
                            @error('destination_id')
                                <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                            @enderror
                        @else
                            {{ $selectedSlip->destination->location_name ?? 'N/A' }}
                        @endif
                    </div>
                </div>

                {{-- Completion Date (only when completed) --}}
                @if (($status == 3 || $status == 4) && $selectedSlip->completed_at)
                    @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
                    <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                        <div class="font-semibold text-gray-500">End Date:</div>
                        <div class="text-gray-900">
                            {{ \Carbon\Carbon::parse($selectedSlip->completed_at)->format('M d, Y - h:i A') }}
                        </div>
                    </div>
                @endif

                {{-- Reason --}}
                @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                    <div class="font-semibold text-gray-500">Reason:<span class="text-red-500">*</span></div>
                    <div class="text-gray-900">
                        @if ($isEditing)
                            <x-forms.searchable-dropdown-paginated wire-model="reason_id" data-method="getPaginatedReasons"
                                search-property="searchReason" placeholder="Select reason..."
                                search-placeholder="Search reasons..." :per-page="20" />
                            @error('reason_id')
                                <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                            @enderror
                        @else
                            {{ $this->displayReason }}
                        @endif
                    </div>
                </div>

                {{-- CAMERA WITH UPLOAD FUNCTIONALITY --}}
                @if (!$isEditing)
                @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
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
                            } catch(err) {
                                alert('Camera error: ' + err.message);
                            }
                        },
                        capturePhoto() {
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
                            const fontSize = Math.max(16, height * 0.03);
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
                            
                            ctx.strokeStyle = '#000000';
                            ctx.lineWidth = fontSize * 0.15;
                            ctx.lineJoin = 'round';
                            ctx.miterLimit = 2;
                            ctx.strokeText(timestamp, textX, textY);
                            
                            ctx.fillStyle = '#FFFFFF';
                            ctx.fillText(timestamp, textX, textY);
                        },
                        async selectFromGallery() {
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
                                        
                                        // Check if still too large
                                        if (sizeInBytes > 15 * 1024 * 1024) {
                                            $wire.dispatch('toast', { 
                                                message: 'Photo \'' + file.name + '\' is too large even after resizing', 
                                                type: 'error' 
                                            });
                                            resolve();
                                            return;
                                        }
                                        
                                        this.photos.push({ id: Date.now(), data: imageData });
                                        resolve();
                                    };
                                    
                                    img.onerror = () => {
                                        $wire.dispatch('toast', { 
                                            message: 'Failed to load image: ' + file.name, 
                                            type: 'error' 
                                        });
                                        resolve();
                                    };
                                    
                                    img.src = e.target.result;
                                };
                                
                                reader.onerror = () => {
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
                            if (this.stream) {
                                this.stream.getTracks().forEach(track => track.stop());
                                this.stream = null;
                            }
                            this.$refs.video.srcObject = null;
                            this.cameraActive = false;
                        },
                        tryCancel() {
                            if (this.photos.length > 0 || this.cameraActive) {
                                this.showCancelConfirmation = true;
                            } else {
                                this.confirmCancel();
                            }
                        },
                        confirmCancel() {
                            this.stopCamera();
                            this.photos = [];
                            this.showCancelConfirmation = false;
                            this.showCameraModal = false;
                            this.uploading = false;
                            this.processingGallery = false;
                        },
                        deletePhoto(id) {
                            this.photos = this.photos.filter(p => p.id !== id);
                        },
                        async uploadPhotos() {
                            if (this.photos.length === 0) {
                                alert('No photos to upload!');
                                return;
                            }
                            
                            this.uploading = true;
                            
                            try {
                                // Collect all photo data into an array
                                const photosData = this.photos.map(photo => photo.data);
                                
                                // Upload all photos at once
                                await $wire.uploadAttachments(photosData);
                                
                                this.photos = [];
                                this.stopCamera();
                                this.showCameraModal = false;
                            } catch(err) {
                                alert('Upload failed: ' + err.message);
                            } finally {
                                this.uploading = false;
                            }
                        }
                    }">
                            @php
                                $photos = $selectedSlip->photos();
                                $attachmentCount = $photos->count();
                            @endphp
                            @if ($attachmentCount > 0)
                                {{-- Mobile Layout --}}
                                <div class="flex flex-col gap-2 md:hidden">
                                    <button wire:click="openAttachmentModal(0)"
                                        class="inline-flex items-center justify-center px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium rounded-lg transition-colors duration-150 cursor-pointer">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        See Photos ({{ $attachmentCount }})
                                    </button>
                                    @if ($this->canManageAttachment())
                                        <button @click="showCameraModal = true; startCamera()"
                                            class="inline-flex items-center justify-center px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors duration-150 cursor-pointer">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            Add More
                                        </button>
                                    @endif
                                </div>

                                {{-- Desktop Layout --}}
                                <div class="hidden md:flex items-center gap-2">
                                    <button wire:click="openAttachmentModal(0)"
                                        class="inline-flex items-center px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium rounded-lg transition-colors duration-150 cursor-pointer">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        See Photos ({{ $attachmentCount }})
                                    </button>
                                    @if ($this->canManageAttachment())
                                        <button @click="showCameraModal = true; startCamera()"
                                            class="inline-flex items-center px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors duration-150 cursor-pointer">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            Add More
                                        </button>
                                    @endif
                                </div>
                            @elseif ($this->canManageAttachment())
                                <button @click="showCameraModal = true; startCamera()"
                                    class="inline-flex items-center px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors duration-150 cursor-pointer">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Add Photos
                                </button>
                            @else
                                N/A
                            @endif

                        {{-- Camera Modal --}}
                        <div x-show="showCameraModal" 
                        x-cloak
                        class="fixed inset-0 z-50 overflow-y-auto"
                        style="display: none;">

                        <div class="fixed inset-0 bg-black/80" @click="tryCancel()"></div>

                        <div class="relative min-h-screen flex items-start sm:items-center justify-center p-2 sm:p-4">
                            <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full p-4 sm:p-6 my-4 sm:my-8 max-h-[95vh] overflow-y-auto">
                                
                                <div class="flex items-center justify-between mb-4 sticky top-0 bg-white z-10 pb-2">
                                    <h3 class="text-base sm:text-lg font-semibold text-gray-900">Add Photos</h3>
                                    <button @click="tryCancel()" class="text-gray-400 hover:text-gray-600 shrink-0">
                                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>

                                <div class="flex flex-col items-center space-y-3 sm:space-y-4">
                                    {{-- Status --}}
                                    <div class="w-full text-center py-2 px-3 sm:px-4 rounded-lg font-medium text-xs sm:text-sm"
                                        :class="uploading ? 'bg-blue-100 text-blue-700' : (processingGallery ? 'bg-purple-100 text-purple-700' : (cameraActive ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'))">
                                        <span x-text="uploading ? 'Uploading...' : (processingGallery ? 'Processing images...' : (cameraActive ? 'Camera is active' : 'Capture or select photos'))"></span>
                                    </div>
                                    
                                    {{-- Camera Preview --}}
                                    <div class="relative w-full max-w-sm aspect-square bg-gray-900 rounded-lg overflow-hidden"
                                        x-show="cameraActive">
                                        <video x-ref="video" class="w-full h-full object-cover" autoplay playsinline></video>
                                        <canvas x-ref="canvas" class="hidden"></canvas>
                                    </div>

                                    {{-- Photos Grid --}}
                                    <div class="w-full" x-show="photos.length > 0">
                                        <h4 class="text-base sm:text-lg font-semibold text-gray-700 mb-2 sm:mb-3">Captured Photos (<span x-text="photos.length"></span>)</h4>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 sm:gap-3 max-h-[40vh] overflow-y-auto pr-1">
                                            <template x-for="photo in photos" :key="photo.id">
                                                <div class="relative rounded-lg overflow-hidden shadow-md">
                                                    <img :src="photo.data" class="w-full h-24 sm:h-32 object-cover">
                                                    @if (!in_array($status, [3, 4]))
                                                        <button @click="deletePhoto(photo.id)" 
                                                            class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white text-xs px-1.5 sm:px-2 py-0.5 sm:py-1 rounded">
                                                            Delete
                                                        </button>
                                                    @endif
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- Footer Buttons --}}
                                <div class="flex flex-wrap justify-center sm:justify-end gap-2 mt-4 sm:mt-6 sticky bottom-0 bg-white pt-2 pb-1 border-t border-gray-100">
                                    <button @click="tryCancel()" 
                                            :disabled="uploading || processingGallery"
                                            class="px-3 sm:px-4 py-1.5 sm:py-2 text-sm sm:text-base bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                        Cancel
                                    </button>
                                    
                                    <button @click="selectFromGallery()" 
                                            x-show="!uploading && !processingGallery"
                                            class="px-3 sm:px-4 py-1.5 sm:py-2 text-sm sm:text-base bg-purple-500 text-white rounded-md hover:bg-purple-600">
                                        Select from Gallery
                                    </button>
                                    
                                    <button @click="startCamera()" 
                                            x-show="!cameraActive && !uploading && !processingGallery"
                                            class="px-3 sm:px-4 py-1.5 sm:py-2 text-sm sm:text-base bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                        Start Camera
                                    </button>
                                    
                                    <button @click="capturePhoto()" 
                                            x-show="cameraActive && !uploading && !processingGallery"
                                            class="px-3 sm:px-4 py-1.5 sm:py-2 text-sm sm:text-base bg-green-500 text-white rounded-md hover:bg-green-600">
                                        Capture Photo
                                    </button>
                                    
                                    <button @click="stopCamera()" 
                                            x-show="cameraActive && !uploading && !processingGallery"
                                            class="px-3 sm:px-4 py-1.5 sm:py-2 text-sm sm:text-base bg-orange-500 text-white rounded-md hover:bg-orange-600">
                                        Stop Camera
                                    </button>
                                    
                                    <button @click="uploadPhotos()" 
                                            x-show="photos.length > 0 && !uploading && !processingGallery"
                                            :disabled="uploading || processingGallery"
                                            class="px-3 sm:px-4 py-1.5 sm:py-2 text-sm sm:text-base bg-blue-600 text-white rounded-md hover:bg-blue-700 font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span x-show="!uploading">Upload <span x-text="photos.length"></span> Photo<span x-show="photos.length > 1">s</span></span>
                                        <span x-show="uploading" class="inline-flex items-center gap-2">
                                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Uploading...
                                        </span>
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
                            </div>  {{-- Close: relative bg-white rounded-lg shadow-xl max-w-2xl w-full p-4 sm:p-6 --}}
                        </div>  {{-- Close: relative min-h-screen flex items-start sm:items-center justify-center p-2 sm:p-4 --}}
                    </div>  {{-- Close: x-show="showCameraModal" camera modal container --}}
                </div>  {{-- Close: x-data Alpine.js container --}}
            </div>  {{-- Close: Photos field text-gray-900 div --}}
        @endif

        {{-- Remarks --}}
        @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; @endphp
        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">                    
            <div class="font-semibold text-gray-500">Remarks:</div>
                    <div class="text-gray-900 wrap-break-words min-w-0" style="word-break: break-word; overflow-wrap: break-word;">
                        @if ($isEditing)
                            <textarea wire:model.live.debounce.500ms="remarks_for_disinfection" class="w-full border rounded px-2 py-2 text-sm" rows="6"></textarea>
                            @error('remarks_for_disinfection')
                                <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                            @enderror
                        @else
                            <div class="whitespace-pre-wrap">{{ $selectedSlip->remarks_for_disinfection ?? 'N/A' }}</div>
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
                                @if ($selectedSlip->hatcheryGuard && !(method_exists($selectedSlip->hatcheryGuard, 'trashed') && $selectedSlip->hatcheryGuard->trashed()))
                                    {{ $selectedSlip->hatcheryGuard->first_name . ' ' . ($selectedSlip->hatcheryGuard->middle_name ?? '') . ' ' . $selectedSlip->hatcheryGuard->last_name }}
                                @elseif ($selectedSlip->hatcheryGuard)
                                    {{ $selectedSlip->hatcheryGuard->first_name . ' ' . ($selectedSlip->hatcheryGuard->middle_name ?? '') . ' ' . $selectedSlip->hatcheryGuard->last_name }}
                                    <span class="text-red-600 font-semibold"> (Deleted)</span>
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-500 mb-0.5">Received By:</div>
                            <div class="text-gray-900">
                                @if ($selectedSlip->receivedGuard && !(method_exists($selectedSlip->receivedGuard, 'trashed') && $selectedSlip->receivedGuard->trashed()))
                                    {{ $selectedSlip->receivedGuard->first_name . ' ' . ($selectedSlip->receivedGuard->middle_name ?? '') . ' ' . $selectedSlip->receivedGuard->last_name }}
                                @elseif ($selectedSlip->receivedGuard)
                                    {{ $selectedSlip->receivedGuard->first_name . ' ' . ($selectedSlip->receivedGuard->middle_name ?? '') . ' ' . $selectedSlip->receivedGuard->last_name }}
                                    <span class="text-red-600 font-semibold"> (Deleted)</span>
                                @else
                                    N/A
                                @endif
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
                {{-- Mobile Layout --}}
                <div class="flex flex-col gap-2 w-full md:hidden">
                    {{-- Start Disinfecting Button (Outgoing Only: Status 0 -> 1) --}}
                    @if ($this->canStartDisinfecting())
                        <x-buttons.submit-button wire:click="$set('showDisinfectingConfirmation', true)" color="red" class="w-full">
                            Start Disinfecting
                        </x-buttons.submit-button>
                    @endif

                    {{-- Complete Button (Outgoing: Status 1 -> 2 | Incoming: Status 2 -> 3) --}}
                    @if ($this->canComplete())
                        <x-buttons.submit-button wire:click="$set('showCompleteConfirmation', true)" color="green" class="w-full">
                            @if ($type === 'outgoing')
                            Complete Disinfection
                            @else
                                Complete Slip
                            @endif
                        </x-buttons.submit-button>
                    @endif

                    {{-- Mark as Incomplete Button (Incoming only: Status 2 -> 1) --}}
                    @if ($type === 'incoming' && $this->canComplete())
                        <x-buttons.submit-button wire:click="$set('showIncompleteConfirmation', true)" color="red" class="w-full">
                            Mark as Incomplete
                        </x-buttons.submit-button>
                    @endif

                    {{-- Edit and Close buttons side by side, 50% each (Outgoing only) --}}
                    @if ($this->canEdit())
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <x-buttons.submit-button
                                wire:click="editDetailsModal"
                                color="blue"
                                class="w-full"
                            >
                                Edit
                            </x-buttons.submit-button>

                            <x-buttons.submit-button
                                wire:click="closeDetailsModal"
                                color="white"
                                class="w-full"
                            >
                                Close
                            </x-buttons.submit-button>
                        </div>
                    @else
                        {{-- Close Button (full width, for Incoming slips) --}}
                        <x-buttons.submit-button
                            wire:click="closeDetailsModal"
                            color="white"
                            class="w-full"
                        >
                            Close
                        </x-buttons.submit-button>
                    @endif
                </div>

                {{-- Desktop Layout --}}
                <div class="hidden md:flex justify-end w-full gap-2">
                        <x-buttons.submit-button wire:click="closeDetailsModal" color="white">
                            Close
                        </x-buttons.submit-button>

                        {{-- Edit Button (Only hatchery guard, matching location, and not completed) --}}
                        @if ($this->canEdit())
                            <x-buttons.submit-button wire:click="editDetailsModal" color="blue">
                                Edit
                            </x-buttons.submit-button>
                        @endif

                        {{-- Start Disinfecting Button (Outgoing Only: Status 0 -> 1) --}}
                        @if ($this->canStartDisinfecting())
                            <x-buttons.submit-button wire:click="$set('showDisinfectingConfirmation', true)" color="red">
                                Start Disinfecting
                            </x-buttons.submit-button>
                        @endif

                        {{-- Complete Button (Outgoing: Status 1 -> 2 | Incoming: Status 2 -> 3) --}}
                        @if ($this->canComplete())
                            <x-buttons.submit-button wire:click="$set('showCompleteConfirmation', true)" color="green">
                                @if ($type === 'outgoing')
                                Complete Disinfection
                                @else
                                    Complete Slip
                                @endif
                            </x-buttons.submit-button>
                        @endif

                        {{-- Mark as Incomplete Button (Incoming only: Status 2 -> 1) --}}
                        @if ($type === 'incoming' && $this->canComplete())
                            <x-buttons.submit-button wire:click="$set('showIncompleteConfirmation', true)" color="red">
                                Mark as Incomplete
                            </x-buttons.submit-button>
                        @endif
                </div>
            @else
                {{-- Mobile Layout for Editing --}}
                <div class="flex flex-col gap-2 w-full md:hidden -mt-8">
                    {{-- Save Button (full width) --}}
                    <x-buttons.submit-button
                        wire:click="save"
                        color="green"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="w-full">
                        <span wire:loading.remove wire:target="save">Save</span>
                        <span wire:loading.inline-flex wire:target="save" class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </x-buttons.submit-button>

                    {{-- Cancel and Delete buttons side by side, 50% each --}}
                    <div class="grid grid-cols-2 gap-2 w-full">
                        <x-buttons.submit-button wire:click="$set('showCancelConfirmation', true)" color="white" class="w-full">
                            Cancel
                        </x-buttons.submit-button>

                        @if ($this->canDelete())
                            <x-buttons.submit-button wire:click="$set('showDeleteConfirmation', true)" color="red" class="w-full">
                                Delete
                            </x-buttons.submit-button>
                        @endif
                    </div>
                </div>

                {{-- Desktop Layout for Editing --}}
                <div class="hidden md:flex justify-between w-full -mt-4">
                    <div>
                        @if ($this->canDelete())
                            <x-buttons.submit-button wire:click="$set('showDeleteConfirmation', true)" color="red">
                                Delete
                            </x-buttons.submit-button>
                        @endif
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
>
                            <span wire:loading.remove wire:target="save">Save</span>
                            <span wire:loading.inline-flex wire:target="save" class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
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
        confirmText="Discard" cancelText="Back" />

    {{-- Delete Confirmation Modal --}}
    <x-modals.delete-confirmation show="showDeleteConfirmation" title="DELETE SLIP?"
        message="Delete this disinfection slip?" :details="'Slip No: <span class=\'font-semibold\'>' . ($selectedSlip?->slip_id ?? '') . '</span>'" warning="This action cannot be undone!"
        onConfirm="deleteSlip" />

    {{-- Disinfecting Confirmation Modal --}}
    <x-modals.modal-template show="showDisinfectingConfirmation" title="START DISINFECTING?" max-width="max-w-md">
        <div class="text-center py-4">
            <p class="text-gray-700 mb-2">Start disinfecting this truck?</p>
            @if ($type === 'incoming')
            <p class="text-sm text-gray-600">You will be assigned as the receiving guard.</p>
            @else
                <p class="text-sm text-gray-600">The slip status will be updated to disinfecting.</p>
            @endif
        </div>

        <x-slot name="footer">
            {{-- Mobile Layout --}}
            <div class="flex flex-col gap-2 w-full -mt-4 md:hidden">
                <x-buttons.submit-button wire:click="startDisinfecting" color="red" wire:loading.attr="disabled" wire:target="startDisinfecting">
                    <span wire:loading.remove wire:target="startDisinfecting">Yes, Start Disinfecting</span>
                    <span wire:loading.inline-flex wire:target="startDisinfecting" class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Starting...
                    </span>
                </x-buttons.submit-button>

                <x-buttons.submit-button wire:click="$set('showDisinfectingConfirmation', false)" color="white" wire:loading.attr="disabled" wire:target="startDisinfecting">
                    Cancel
                </x-buttons.submit-button>
            </div>

            {{-- Desktop Layout --}}
            <div class="hidden md:flex justify-end gap-3">
                <x-buttons.submit-button wire:click="$set('showDisinfectingConfirmation', false)" color="white" wire:loading.attr="disabled" wire:target="startDisinfecting">
                    Cancel
                </x-buttons.submit-button>
                <x-buttons.submit-button wire:click="startDisinfecting" color="red" wire:loading.attr="disabled" wire:target="startDisinfecting">
                    <span wire:loading.remove wire:target="startDisinfecting">Yes, Start Disinfecting</span>
                    <span wire:loading.inline-flex wire:target="startDisinfecting" class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Starting...
                    </span>
                </x-buttons.submit-button>
            </div>
        </x-slot>
    </x-modals.modal-template>

    {{-- Complete Confirmation Modal --}}
    <x-modals.modal-template show="showCompleteConfirmation" :title="$type === 'outgoing' ? 'COMPLETE DISINFECTION?' : 'COMPLETE SLIP?'" max-width="max-w-md">
        <div class="text-center py-4">
            @if ($type === 'outgoing')
                <p class="text-gray-700 mb-2">Complete disinfection for this truck?</p>
                <p class="text-sm text-gray-600">The slip will be marked as In-Transit and ready for destination.</p>
            @else
                <p class="text-gray-700 mb-2">Complete this slip?</p>
                <p class="text-sm text-gray-600">This will mark the slip as completed. This action cannot be undone.</p>
            @endif
        </div>

        <x-slot name="footer">
            {{-- Mobile Layout --}}
            <div class="flex flex-col gap-2 w-full -mt-4 md:hidden">
                <x-buttons.submit-button wire:click="completeDisinfection" color="green" wire:loading.attr="disabled" wire:target="completeDisinfection">
                    <span wire:loading.remove wire:target="completeDisinfection">Yes, Complete</span>
                    <span wire:loading.inline-flex wire:target="completeDisinfection" class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Completing...
                    </span>
                </x-buttons.submit-button>

                <x-buttons.submit-button wire:click="$set('showCompleteConfirmation', false)" color="white" wire:loading.attr="disabled" wire:target="completeDisinfection">
                    Cancel
                </x-buttons.submit-button>
            </div>

            {{-- Desktop Layout --}}
            <div class="hidden md:flex justify-end gap-3">
                <x-buttons.submit-button wire:click="$set('showCompleteConfirmation', false)" color="white" wire:loading.attr="disabled" wire:target="completeDisinfection">
                    Cancel
                </x-buttons.submit-button>
                <x-buttons.submit-button wire:click="completeDisinfection" color="green" wire:loading.attr="disabled" wire:target="completeDisinfection">
                    <span wire:loading.remove wire:target="completeDisinfection">Yes, Complete</span>
                    <span wire:loading.inline-flex wire:target="completeDisinfection" class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Completing...
                    </span>
                </x-buttons.submit-button>
            </div>
        </x-slot>
    </x-modals.modal-template>

    {{-- Mark as Incomplete Confirmation Modal (Incoming only) --}}
    <x-modals.modal-template show="showIncompleteConfirmation" title="MARK AS INCOMPLETE?" max-width="max-w-md">
        <div class="text-center py-4">
            <p class="text-gray-700 mb-2">Mark this disinfection slip as incomplete?</p>
            <p class="text-sm text-gray-600">This will reset the status to disinfecting and allow the process to be restarted.</p>
        </div>

        <x-slot name="footer">
            {{-- Mobile Layout --}}
            <div class="flex flex-col gap-2 w-full -mt-4 md:hidden">
                <x-buttons.submit-button wire:click="markAsIncomplete" color="red" wire:loading.attr="disabled" wire:target="markAsIncomplete">
                    <span wire:loading.remove wire:target="markAsIncomplete">Yes, Mark as Incomplete</span>
                    <span wire:loading.inline-flex wire:target="markAsIncomplete" class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Marking as Incomplete...
                    </span>
                </x-buttons.submit-button>

                <x-buttons.submit-button wire:click="$set('showIncompleteConfirmation', false)" color="white" wire:loading.attr="disabled" wire:target="markAsIncomplete">
                    Cancel
                </x-buttons.submit-button>
            </div>

            {{-- Desktop Layout --}}
            <div class="hidden md:flex justify-end gap-3">
                <x-buttons.submit-button wire:click="$set('showIncompleteConfirmation', false)" color="white" wire:loading.attr="disabled" wire:target="markAsIncomplete">
                    Cancel
                </x-buttons.submit-button>
                <x-buttons.submit-button wire:click="markAsIncomplete" color="red" wire:loading.attr="disabled" wire:target="markAsIncomplete">
                    <span wire:loading.remove wire:target="markAsIncomplete">Yes, Mark as Incomplete</span>
                    <span wire:loading.inline-flex wire:target="markAsIncomplete" class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Marking as Incomplete...
                    </span>
                </x-buttons.submit-button>
            </div>
        </x-slot>
    </x-modals.modal-template>

    {{-- Remove Photo Confirmation Modal --}}
    <x-modals.modal-template show="showRemoveAttachmentConfirmation" title="REMOVE PHOTO?" max-width="max-w-md">
        <div class="text-center py-4">
            <p class="text-gray-700 mb-2">Are you sure you want to remove this photo?</p>
            <p class="text-sm text-red-600 font-semibold">This action cannot be undone!</p>
            <p class="text-sm text-gray-600">The file will be permanently deleted.</p>
        </div>

        <x-slot name="footer">
            {{-- Mobile Layout --}}
            <div class="flex flex-col gap-2 w-full -mt-4 md:hidden">
                <x-buttons.submit-button wire:click="removeAttachment" color="red">
                    Yes, Remove Photo
                </x-buttons.submit-button>

                <x-buttons.submit-button wire:click="$set('showRemoveAttachmentConfirmation', false)" color="white">
                    Cancel
                </x-buttons.submit-button>
            </div>

            {{-- Desktop Layout --}}
            <div class="hidden md:flex justify-end gap-3">
                <x-buttons.submit-button wire:click="$set('showRemoveAttachmentConfirmation', false)" color="white">
                    Cancel
                </x-buttons.submit-button>
                <x-buttons.submit-button wire:click="removeAttachment" color="red">
                    Yes, Remove Photo
                </x-buttons.submit-button>
            </div>
        </x-slot>
    </x-modals.modal-template>

    {{-- Photo Gallery Modal --}}
    <x-modals.Photo show="showAttachmentModal" :selectedSlip="$selectedSlip" />

    {{-- Add Photos Modal is now inline Alpine.js modal above --}}

    {{-- Report Issue Modal --}}
    <x-modals.modal-template show="showIssueModal" title="REPORT AN ISSUE" max-width="max-w-3xl"
        header-class="border-t-4 border-t-red-500 bg-red-50">
        @if ($selectedSlip)
            {{-- Sub Header --}}
            <div class="border-b border-gray-200 px-6 py-2 bg-gray-50 -mx-6 -mt-6 mb-2">
                <div class="grid grid-cols-2 gap-4 items-start text-xs">
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

            {{-- Body --}}
            <div class="space-y-0 -mx-6">
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                    <div class="font-semibold text-gray-500">Description:</div>
                    <div class="text-gray-900">
                        <textarea wire:model="issueDescription" rows="6"
                            class="w-full border rounded px-2 py-2 text-sm border-gray-300 focus:border-red-500 focus:ring-red-500"
                            placeholder="Please describe the issue for reporting this slip..."></textarea>
                        @error('issueDescription')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Minimum 10 characters required.</p>
                    </div>
                </div>
            </div>
        @endif

        <x-slot name="footer">
            <div class="flex flex-col gap-2 w-full -mt-8">
                <x-buttons.submit-button wire:click.prevent="submitReport" color="red" wire:loading.attr="disabled" wire:target="submitReport"
                    x-bind:disabled="$wire.isSubmitting">
                    <span wire:loading.remove wire:target="submitReport">Submit Issue</span>
                    <span wire:loading.inline-flex wire:target="submitReport" class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Submitting...
                    </span>
                </x-buttons.submit-button>

                <x-buttons.submit-button wire:click="closeIssueModal" color="white" wire:loading.attr="disabled" wire:target="submitReport">
                    Cancel
                </x-buttons.submit-button>
            </div>
        </x-slot>
    </x-modals.modal-template>

</div>
