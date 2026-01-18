<div>
    {{-- CREATE MODAL --}}
    <x-modals.modal-template show="showModal" title="CREATE NEW DISINFECTION SLIP" max-width="max-w-3xl">
        <x-slot name="headerActions">
            <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 transition hover:cursor-pointer">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </x-slot>

        {{-- Body Fields --}}
        @php
            // Track row index for alternating colors
            $rowIndex = 0;
        @endphp
        <div class="space-y-0 -mx-6">
            {{-- Vehicle --}}
            @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
            <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                <div class="font-semibold text-gray-500">Vehicle:<span class="text-red-500">*</span></div>
                <div class="text-gray-900 min-w-0">
                    <x-forms.searchable-dropdown-paginated wire-model="vehicle_id" data-method="getPaginatedVehicles" search-property="searchVehicle"
                        placeholder="Select vehicle..." search-placeholder="Search vehicles..." :per-page="20" />
                    @error('vehicle_id')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            @if (!$useGuardMode)
            {{-- Origin (Admin mode only) --}}
            @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
            <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                <div class="font-semibold text-gray-500">Origin:<span class="text-red-500">*</span></div>
                <div class="text-gray-900 min-w-0">
                    <x-forms.searchable-dropdown-paginated wire-model="location_id" data-method="getPaginatedLocations" search-property="searchOrigin"
                        placeholder="Select origin..." search-placeholder="Search locations..." :per-page="20" />
                    @error('location_id')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            @endif

            {{-- Destination --}}
            @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
            <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                <div class="font-semibold text-gray-500">Destination:<span class="text-red-500">*</span></div>
                <div class="text-gray-900 min-w-0">
                    <x-forms.searchable-dropdown-paginated wire-model="destination_id" data-method="getPaginatedLocations" search-property="searchDestination"
                        placeholder="Select destination..." search-placeholder="Search locations..." :per-page="20" />
                    @error('destination_id')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Driver Name --}}
            @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
            <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                <div class="font-semibold text-gray-500">Driver Name:<span class="text-red-500">*</span></div>
                <div class="text-gray-900 min-w-0">
                    <x-forms.searchable-dropdown-paginated wire-model="driver_id" data-method="getPaginatedDrivers" search-property="searchDriver"
                        placeholder="Select driver..." search-placeholder="Search drivers..." :per-page="20" />
                    @error('driver_id')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            @if (!$useGuardMode)
            {{-- Hatchery Guard (Admin mode only) --}}
            @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
            <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                <div class="font-semibold text-gray-500">Hatchery Guard:<span class="text-red-500">*</span></div>
                <div class="text-gray-900 min-w-0">
                    <x-forms.searchable-dropdown-paginated wire-model="hatchery_guard_id" data-method="getPaginatedGuards"
                        search-property="searchHatcheryGuard" placeholder="Select hatchery guard..."
                        search-placeholder="Search guards..." :per-page="20" />
                    @error('hatchery_guard_id')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            @endif

            @if (!$useGuardMode)
            {{-- Receiving Guard (Admin mode only) --}}
            @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
            <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                <div class="font-semibold text-gray-500">Receiving Guard:</div>
                <div class="text-gray-900 min-w-0">
                    <x-forms.searchable-dropdown-paginated wire-model="received_guard_id" data-method="getPaginatedGuards"
                        search-property="searchReceivedGuard" placeholder="Select receiving guard..."
                        search-placeholder="Search guards..." :per-page="20" />
                    @error('received_guard_id')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            @endif

            {{-- Reason --}}
            @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
            <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                <div class="font-semibold text-gray-500">Reason:<span class="text-red-500">*</span></div>
                <div class="text-gray-900 min-w-0">
                    <x-forms.searchable-dropdown-paginated wire-model="reason_id" data-method="getPaginatedReasons" search-property="searchReason"
                        placeholder="Select reason..." search-placeholder="Search reasons..." :per-page="20" />
                    @error('reason_id')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            @if ($useGuardMode)
            {{-- Photos (Guard mode only) --}}
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
                                    ctx.drawImage(video, 0, 0);
                                    this.addTimestampWatermark(ctx, canvas.width, canvas.height);
                                    const imageData = canvas.toDataURL('image/jpeg', 0.85);
                                    this.photos.push({ id: Date.now(), data: imageData });
                                },
                                addTimestampWatermark(ctx, width, height) {
                                    const now = new Date();
                                    const dateStr = now.toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' });
                                    const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
                                    const timestamp = `${dateStr} ${timeStr}`;
                                    const fontSize = Math.max(16, height * 0.03);
                                    ctx.font = `bold ${fontSize}px Arial`;
                                    ctx.textBaseline = 'bottom';
                                    const padding = fontSize * 0.3;
                                    const textWidth = ctx.measureText(timestamp).width;
                                    const textHeight = fontSize;
                                    const bgX = padding;
                                    const bgY = height - textHeight - padding * 2;
                                    const bgWidth = textWidth + padding * 2;
                                    const bgHeight = textHeight + padding * 2;
                                    ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
                                    ctx.fillRect(bgX, bgY, bgWidth, bgHeight);
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
                                                const maxDimension = 640;
                                                let targetWidth = img.width;
                                                let targetHeight = img.height;
                                                if (img.width > maxDimension || img.height > maxDimension) {
                                                    const scale = Math.min(maxDimension / img.width, maxDimension / img.height);
                                                    targetWidth = Math.floor(img.width * scale);
                                                    targetHeight = Math.floor(img.height * scale);
                                                }
                                                canvas.width = targetWidth;
                                                canvas.height = targetHeight;
                                                ctx.drawImage(img, 0, 0, targetWidth, targetHeight);
                                                this.addTimestampWatermark(ctx, canvas.width, canvas.height);
                                                const imageData = canvas.toDataURL('image/jpeg', 0.85);
                                                const base64Length = imageData.length - 'data:image/jpeg;base64,'.length;
                                                const sizeInBytes = (base64Length * 3) / 4;
                                                if (sizeInBytes > 15 * 1024 * 1024) {
                                                    $wire.dispatch('toast', { message: 'Photo \'' + file.name + '\' is too large even after resizing', type: 'error' });
                                                    resolve();
                                                    return;
                                                }
                                                this.photos.push({ id: Date.now(), data: imageData });
                                                resolve();
                                            };
                                            img.onerror = () => {
                                                $wire.dispatch('toast', { message: 'Failed to load image: ' + file.name, type: 'error' });
                                                resolve();
                                            };
                                            img.src = e.target.result;
                                        };
                                        reader.onerror = () => {
                                            $wire.dispatch('toast', { message: 'Failed to read file: ' + file.name, type: 'error' });
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
                                        const photosData = this.photos.map(photo => photo.data);
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
                                <div x-show="showCameraModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                                    <div class="fixed inset-0 bg-black/80" @click="tryCancel()"></div>
                                    <div class="relative min-h-screen flex items-center justify-center p-4">
                                        <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full p-6" @click.stop>
                                            <div class="flex items-center justify-between mb-4">
                                                <h3 class="text-lg font-semibold text-gray-900">Add Photos</h3>
                                                <button @click="tryCancel()" class="text-gray-400 hover:text-gray-600">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="flex flex-col items-center space-y-4">
                                                <div class="w-full text-center py-2 px-4 rounded-lg font-medium text-sm"
                                                    :class="uploading ? 'bg-blue-100 text-blue-700' : (processingGallery ? 'bg-purple-100 text-purple-700' : (cameraActive ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'))">
                                                    <span x-text="uploading ? 'Uploading...' : (processingGallery ? 'Processing images...' : (cameraActive ? 'Camera is active' : 'Capture or select photos'))"></span>
                                                </div>
                                                <div class="relative w-80 h-80 bg-gray-900 rounded-lg overflow-hidden" x-show="cameraActive">
                                                    <video x-ref="video" class="w-full h-full object-cover" autoplay playsinline></video>
                                                    <canvas x-ref="canvas" class="hidden"></canvas>
                                                </div>
                                                <div class="w-full" x-show="photos.length > 0">
                                                    <h4 class="text-lg font-semibold text-gray-700 mb-3">Captured Photos (<span x-text="photos.length"></span>)</h4>
                                                    <div class="grid grid-cols-3 gap-3">
                                                        <template x-for="photo in photos" :key="photo.id">
                                                            <div class="relative rounded-lg overflow-hidden shadow-md">
                                                                <img :src="photo.data" class="w-full h-24 object-cover">
                                                                <button @click="deletePhoto(photo.id)" class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white text-xs px-2 py-1 rounded">Delete</button>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex flex-col gap-2 mt-6 w-full md:hidden">
                                                <button @click="selectFromGallery()" x-show="!uploading && !processingGallery" class="w-full px-4 py-2 bg-purple-500 text-white rounded-md hover:bg-purple-600">Select from Gallery</button>
                                                <button @click="startCamera()" x-show="!cameraActive && !uploading && !processingGallery" class="w-full px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Start Camera</button>
                                                <button @click="capturePhoto()" x-show="cameraActive && !uploading && !processingGallery" class="w-full px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">Capture Photo</button>
                                                <button @click="stopCamera()" x-show="cameraActive && !uploading && !processingGallery" class="w-full px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600">Stop Camera</button>
                                                <button @click="uploadPhotos()" x-show="photos.length > 0 && !uploading && !processingGallery" :disabled="uploading || processingGallery" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <span x-show="!uploading">Upload <span x-text="photos.length"></span> Photo<span x-show="photos.length > 1">s</span></span>
                                                    <span x-show="uploading" class="inline-flex items-center gap-2">
                                                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Uploading...
                                                    </span>
                                                </button>
                                                <button @click="tryCancel()" :disabled="uploading || processingGallery" class="w-full px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Cancel</button>
                                            </div>
                                            <div class="hidden md:flex justify-end gap-2 mt-6">
                                                <button @click="tryCancel()" :disabled="uploading || processingGallery" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Cancel</button>
                                                <button @click="selectFromGallery()" x-show="!uploading && !processingGallery" class="px-4 py-2 bg-purple-500 text-white rounded-md hover:bg-purple-600">Select from Gallery</button>
                                                <button @click="startCamera()" x-show="!cameraActive && !uploading && !processingGallery" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Start Camera</button>
                                                <button @click="capturePhoto()" x-show="cameraActive && !uploading && !processingGallery" class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">Capture Photo</button>
                                                <button @click="stopCamera()" x-show="cameraActive && !uploading && !processingGallery" class="px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600">Stop Camera</button>
                                                <button @click="uploadPhotos()" x-show="photos.length > 0 && !uploading && !processingGallery" :disabled="uploading || processingGallery" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
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
                                            <div x-show="showCancelConfirmation" x-cloak class="fixed inset-0 z-60 overflow-y-auto" style="display: none;">
                                                <div class="fixed inset-0 bg-black/50"></div>
                                                <div class="relative min-h-screen flex items-center justify-center p-4">
                                                    <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                                                        <div class="text-center">
                                                            <div class="mx-auto mb-4 text-yellow-500 w-16 h-16">
                                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                                </svg>
                                                            </div>
                                                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Cancel Photo Capture?</h3>
                                                            <p class="text-gray-700 mb-4">Any captured photos that haven't been uploaded will be lost.</p>
                                                            <div class="flex gap-3 justify-center">
                                                                <button @click="showCancelConfirmation = false" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Continue Capturing</button>
                                                                <button @click="confirmCancel()" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Yes, Cancel</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                </div>
            </div>
            @endif

            {{-- Remarks for Disinfection --}}
            @php $bgClass = ($rowIndex % 2 === 0) ? 'bg-white' : 'bg-gray-100'; $rowIndex++; @endphp
            <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs {{ $bgClass }}">
                <div class="font-semibold text-gray-500">Remarks:</div>
                <div class="text-gray-900">
                    <textarea wire:model="remarks_for_disinfection"
                        class="w-full border rounded px-2 py-1 text-sm border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        rows="6" placeholder="Enter remarks..."></textarea>
                    @error('remarks_for_disinfection')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <x-slot name="footer">
            <x-buttons.submit-button wire:click="closeModal" color="white" wire:loading.attr="disabled" wire:target="createSlip">
                Cancel
            </x-buttons.submit-button>

            <x-buttons.submit-button wire:click.prevent="createSlip" color="blue" wire:loading.attr="disabled" wire:target="createSlip">
                <span wire:loading.remove wire:target="createSlip">Create</span>
                <span wire:loading.inline-flex wire:target="createSlip" class="inline-flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Creating...
                </span>
            </x-buttons.submit-button>
        </x-slot>

    </x-modals.modal-template>

    {{-- Cancel Confirmation Modal --}}
    <x-modals.unsaved-confirmation show="showCancelConfirmation" title="DISCARD CHANGES?"
        message="Are you sure you want to cancel?" warning="All unsaved changes will be lost." onConfirm="cancelCreate"
        confirmText="Discard" cancelText="Back" />
    
    @if ($useGuardMode)
    {{-- Remove Pending Photo Confirmation Modal --}}
    <x-modals.confirmation-modal show="showRemovePendingAttachmentConfirmation" title="DELETE PHOTO?"
        message="Are you sure you want to delete this photo?" warning="This action cannot be undone."
        onConfirm="removePendingAttachment" confirmText="Delete" cancelText="Cancel" />

    {{-- Pending Photos Carousel Modal --}}
    <x-modals.modal-template show="showPendingAttachmentModal" title="Photos" max-width="w-[96%] sm:max-w-4xl" backdrop-opacity="40">
        @php
            $pendingAttachments = $this->pendingAttachments;
            $totalPendingAttachments = $this->totalPendingAttachments;
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
                        @foreach ($pendingAttachments as $index => $Photo)
                            @php
                                $fileUrl = \Illuminate\Support\Facades\Storage::url($Photo->file_path);
                                $extension = strtolower(pathinfo($Photo->file_path ?? '', PATHINFO_EXTENSION));
                                $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                                $isImage = in_array($extension, $imageExtensions);
                            @endphp
                            <div class="w-full shrink-0 px-2 sm:px-4 py-2 sm:py-4 flex flex-col" style="min-width: 100%">
                                @if ($isImage)
                                    <img src="{{ $fileUrl }}" 
                                         class="border shadow-md max-h-[45vh] sm:max-h-[55vh] max-w-full w-auto object-contain mx-auto rounded-lg"
                                         alt="Photo {{ $index + 1 }}">
                                @else
                                    <div class="text-center p-4 sm:p-8">
                                        <p class="text-xs sm:text-sm text-gray-600 mb-2 sm:mb-4">This file type cannot be previewed.</p>
                                        <a href="{{ $fileUrl }}" target="_blank" 
                                           class="text-orange-500 font-semibold underline hover:cursor-pointer cursor-pointer text-sm sm:text-base">Download photo</a>
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
                        @foreach ($pendingAttachments as $index => $Photo)
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
            <div class="text-center p-8 text-gray-500">No photos available.</div>
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
                        $attachmentsMap = $pendingAttachments->mapWithKeys(fn($a) => [$a->id => $a->user_id])->toArray();
                        $attachmentsKey = implode(',', $pendingAttachmentIds ?? []);
                    @endphp
                    <div wire:key="pending-photos-{{ $attachmentsKey }}" x-data="{
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
                            if (this.initialAttachmentsMap[attachmentId]) {
                                return this.initialAttachmentsMap[attachmentId];
                            }
                            return null;
                        },
                        get canDelete() {
                            const attachmentId = this.getCurrentAttachmentId();
                            if (!attachmentId) return false;
                            const attachmentUserId = this.getAttachmentUserId(attachmentId);
                            if (attachmentUserId === null) return false;
                            return this.isAdminOrSuperAdmin || attachmentUserId === this.currentUserId;
                        },
                        deleteCurrentPhoto() {
                            const attachmentId = this.getCurrentAttachmentId();
                            if (attachmentId) {
                                $wire.call('confirmRemovePendingAttachment', attachmentId);
                            }
                        }
                    }" x-init="
                        $watch(() => $wire.currentPendingAttachmentIndex, (newIndex) => { this.currentIndex = newIndex; });
                        $watch(() => $wire.pendingAttachmentIds, (newIds) => {
                            this.attachmentIds = newIds || [];
                            const currentIds = new Set(this.attachmentIds);
                            if (this.initialAttachmentsMap && typeof this.initialAttachmentsMap === 'object') {
                                Object.keys(this.initialAttachmentsMap).forEach(id => {
                                    if (!currentIds.has(Number(id))) {
                                        delete this.initialAttachmentsMap[id];
                                    }
                                });
                            }
                        });
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
    @endif
</div>
