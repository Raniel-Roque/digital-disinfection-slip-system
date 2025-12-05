@props(['show'])

<x-modals.modal-template :show="$show" title="Add Attachment" max-width="max-w-2xl">

    <div class="flex flex-col items-center p-4 space-y-4">
        <!-- Camera Preview (Square) -->
        <div class="relative w-80 h-80 bg-gray-900 rounded-lg overflow-hidden">
            <video id="cameraPreview" class="w-full h-full object-cover" autoplay playsinline></video>
            <canvas id="photoCanvas" class="hidden"></canvas>
        </div>

        <!-- Captured Photo Preview -->
        <div id="capturedPhotoContainer" class="hidden w-80 h-80">
            <img id="capturedPhoto" class="w-full h-full object-cover rounded-lg" alt="Captured photo">
        </div>

        <!-- Status Message -->
        <p id="statusMessage" class="text-sm text-gray-600"></p>
    </div>

    <x-slot name="footer">
        <!-- Cancel Button (Always visible) -->
        <x-buttons.submit-button wire:click="closeAddAttachmentModal" color="white">
            Cancel
        </x-buttons.submit-button>

        <!-- Switch Camera Button (Only visible when camera is active) -->
        <x-buttons.submit-button id="switchCameraBtn" color="gray" class="hidden">
            Switch Camera
        </x-buttons.submit-button>

        <!-- Capture/Retake Button -->
        <x-buttons.submit-button id="captureBtn" color="blue">
            Capture Photo
        </x-buttons.submit-button>

        <x-buttons.submit-button id="retakeBtn" color="gray" class="hidden">
            Retake
        </x-buttons.submit-button>

        <!-- Upload Button (Hidden until photo is captured) -->
        <x-buttons.submit-button id="uploadBtn" color="green" class="hidden">
            Upload
        </x-buttons.submit-button>
    </x-slot>

    @push('scripts')
        <script>
            let stream = null;
            let capturedImageData = null;
            let currentFacingMode = 'environment'; // Start with back camera, fallback to front

            // Helper function to get elements safely
            function getElement(id) {
                return document.getElementById(id);
            }

            // Initialize camera management
            function initCameraManagement() {
                // Start camera when modal opens via Livewire event
                document.addEventListener('livewire:init', () => {
                    // Listen for the event to open modal
                    Livewire.on('showAddAttachmentModal', () => {
                        // Wait for modal to render
                        setTimeout(() => {
                            startCamera();
                        }, 300);
                    });

                    // Watch for Livewire property changes
                    Livewire.hook('message.processed', ({
                        component
                    }) => {
                        if (component) {
                            try {
                                const isOpen = component.get('showAddAttachmentModal');
                                if (!isOpen && stream) {
                                    // Modal closed, stop camera
                                    stopCamera();
                                    resetCamera();
                                } else if (isOpen && !stream) {
                                    // Modal opened, start camera
                                    setTimeout(() => {
                                        startCamera();
                                    }, 300);
                                }
                            } catch (e) {
                                // Component might not have the property, ignore
                            }
                        }
                    });
                });

                // Watch for modal visibility changes (handles Alpine.js closes via backdrop/X)
                const observer = new MutationObserver(() => {
                    const video = getElement('cameraPreview');
                    if (video) {
                        const modal = video.closest('[x-data]');
                        if (modal) {
                            // Check if modal is visible
                            const isVisible = window.getComputedStyle(modal).display !== 'none';

                            if (!isVisible && stream) {
                                // Modal hidden, stop camera
                                stopCamera();
                                resetCamera();
                            } else if (isVisible && !stream && video.offsetParent !== null) {
                                // Modal visible and video element is in view, start camera
                                setTimeout(() => {
                                    startCamera();
                                }, 300);
                            }
                        }
                    }
                });

                // Start observing when DOM is ready
                if (document.body) {
                    observer.observe(document.body, {
                        childList: true,
                        subtree: true,
                        attributes: true,
                        attributeFilter: ['style']
                    });
                }
            }

            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    initCameraManagement();
                    setupButtonListeners();
                });
            } else {
                initCameraManagement();
                setupButtonListeners();
            }

            // Set up event listeners for buttons
            function setupButtonListeners() {
                // Use a small delay to ensure buttons are rendered
                setTimeout(() => {
                    const captureBtn = getElement('captureBtn');
                    const retakeBtn = getElement('retakeBtn');
                    const uploadBtn = getElement('uploadBtn');
                    const switchCameraBtn = getElement('switchCameraBtn');

                    if (captureBtn) {
                        captureBtn.addEventListener('click', capturePhoto);
                    }
                    if (retakeBtn) {
                        retakeBtn.addEventListener('click', retakePhoto);
                    }
                    if (uploadBtn) {
                        uploadBtn.addEventListener('click', uploadCapturedPhoto);
                    }
                    if (switchCameraBtn) {
                        switchCameraBtn.addEventListener('click', switchCamera);
                    }
                }, 100);
            }

            async function startCamera(facingMode = null) {
                const video = getElement('cameraPreview');
                const statusMessage = getElement('statusMessage');
                const capturedPhotoContainer = getElement('capturedPhotoContainer');
                const switchCameraBtn = getElement('switchCameraBtn');

                if (!video || !statusMessage) {
                    console.error('Camera elements not found');
                    return;
                }

                // Use provided facingMode or current one
                const targetFacingMode = facingMode || currentFacingMode;

                try {
                    // Stop any existing stream first
                    if (stream) {
                        stopCamera();
                    }

                    // Try to get the requested camera
                    try {
                        stream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                facingMode: targetFacingMode,
                                width: {
                                    ideal: 640
                                },
                                height: {
                                    ideal: 480
                                }
                            }
                        });
                        currentFacingMode = targetFacingMode;
                    } catch (primaryError) {
                        // If back camera fails, try front camera (for laptops)
                        if (targetFacingMode === 'environment') {
                            console.log('Back camera not available, trying front camera...');
                            try {
                                stream = await navigator.mediaDevices.getUserMedia({
                                    video: {
                                        facingMode: 'user', // Front camera
                                        width: {
                                            ideal: 640
                                        },
                                        height: {
                                            ideal: 480
                                        }
                                    }
                                });
                                currentFacingMode = 'user';
                                if (statusMessage) {
                                    statusMessage.textContent = 'Using front camera (back camera not available)';
                                }
                            } catch (fallbackError) {
                                throw primaryError; // Throw original error if both fail
                            }
                        } else {
                            throw primaryError;
                        }
                    }

                    video.srcObject = stream;
                    if (video.parentElement) {
                        video.parentElement.classList.remove('hidden');
                    }
                    if (capturedPhotoContainer) {
                        capturedPhotoContainer.classList.add('hidden');
                    }

                    // Show switch camera button if multiple cameras are available
                    if (switchCameraBtn) {
                        // Check if device has multiple cameras
                        const devices = await navigator.mediaDevices.enumerateDevices();
                        const videoDevices = devices.filter(device => device.kind === 'videoinput');
                        if (videoDevices.length > 1) {
                            switchCameraBtn.classList.remove('hidden');
                        }
                    }

                    if (statusMessage && !statusMessage.textContent.includes('front camera')) {
                        statusMessage.textContent = 'Camera ready';
                    }
                    statusMessage.classList.remove('text-red-600');
                    statusMessage.classList.add('text-gray-600');
                } catch (error) {
                    if (statusMessage) {
                        let errorMsg = 'Error accessing camera: ' + error.message;
                        if (error.name === 'NotAllowedError') {
                            errorMsg = 'Camera permission denied. Please allow camera access.';
                        } else if (error.name === 'NotFoundError') {
                            errorMsg = 'No camera found. Please connect a camera.';
                        }
                        statusMessage.textContent = errorMsg;
                        statusMessage.classList.remove('text-gray-600');
                        statusMessage.classList.add('text-red-600');
                    }
                    console.error('Camera error:', error);
                }
            }

            function switchCamera() {
                // Toggle between front and back camera
                const newFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
                startCamera(newFacingMode);
            }

            function stopCamera() {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    stream = null;
                }
            }

            function capturePhoto() {
                const video = getElement('cameraPreview');
                const canvas = getElement('photoCanvas');
                const statusMessage = getElement('statusMessage');

                if (!video || !canvas || !statusMessage) {
                    console.error('Camera elements not found');
                    return;
                }

                const context = canvas.getContext('2d');

                // Set canvas to square dimensions
                canvas.width = 640;
                canvas.height = 640;

                // Draw the video frame to canvas
                context.drawImage(video, 0, 0, canvas.width, canvas.height);

                // Convert to data URL (base64)
                const imageDataUrl = canvas.toDataURL('image/jpeg', 0.85);

                // Check size
                const sizeInBytes = Math.round((imageDataUrl.length - 'data:image/jpeg;base64,'.length) * 0.75);
                const sizeInMB = sizeInBytes / (1024 * 1024);

                if (sizeInMB > 15) {
                    // Compress more if too large
                    const compressedImageData = canvas.toDataURL('image/jpeg', 0.7);
                    const compressedSize = Math.round((compressedImageData.length - 'data:image/jpeg;base64,'.length) * 0.75);
                    const compressedSizeMB = compressedSize / (1024 * 1024);

                    if (compressedSizeMB > 15) {
                        statusMessage.textContent = 'Image too large even after compression. Please try again.';
                        statusMessage.classList.remove('text-gray-600');
                        statusMessage.classList.add('text-red-600');
                        return;
                    }

                    capturedImageData = compressedImageData;
                    displayCapturedPhoto(compressedImageData, compressedSizeMB);
                } else {
                    capturedImageData = imageDataUrl;
                    displayCapturedPhoto(imageDataUrl, sizeInMB);
                }
            }

            function displayCapturedPhoto(imageDataUrl, sizeMB) {
                const video = getElement('cameraPreview');
                const capturedPhoto = getElement('capturedPhoto');
                const capturedPhotoContainer = getElement('capturedPhotoContainer');
                const captureBtn = getElement('captureBtn');
                const retakeBtn = getElement('retakeBtn');
                const uploadBtn = getElement('uploadBtn');
                const switchCameraBtn = getElement('switchCameraBtn');
                const statusMessage = getElement('statusMessage');

                if (!capturedPhoto || !capturedPhotoContainer || !statusMessage) {
                    console.error('Display elements not found');
                    return;
                }

                capturedPhoto.src = imageDataUrl;

                // Hide camera, show captured photo
                if (video && video.parentElement) {
                    video.parentElement.classList.add('hidden');
                }
                capturedPhotoContainer.classList.remove('hidden');

                // Toggle buttons
                if (captureBtn) captureBtn.classList.add('hidden');
                if (switchCameraBtn) switchCameraBtn.classList.add('hidden');
                if (retakeBtn) retakeBtn.classList.remove('hidden');
                if (uploadBtn) uploadBtn.classList.remove('hidden');

                stopCamera();

                statusMessage.textContent = `Photo captured (${sizeMB.toFixed(2)} MB)`;
                statusMessage.classList.remove('text-red-600');
                statusMessage.classList.add('text-gray-600');
            }

            function retakePhoto() {
                const capturedPhotoContainer = getElement('capturedPhotoContainer');
                const retakeBtn = getElement('retakeBtn');
                const uploadBtn = getElement('uploadBtn');
                const captureBtn = getElement('captureBtn');

                if (!capturedPhotoContainer) {
                    console.error('Elements not found');
                    return;
                }

                // Hide captured photo, show camera
                capturedPhotoContainer.classList.add('hidden');

                // Toggle buttons
                if (retakeBtn) retakeBtn.classList.add('hidden');
                if (uploadBtn) uploadBtn.classList.add('hidden');
                if (captureBtn) captureBtn.classList.remove('hidden');

                capturedImageData = null;
                startCamera();
            }

            function resetCamera() {
                const capturedPhotoContainer = getElement('capturedPhotoContainer');
                const retakeBtn = getElement('retakeBtn');
                const uploadBtn = getElement('uploadBtn');
                const captureBtn = getElement('captureBtn');
                const switchCameraBtn = getElement('switchCameraBtn');
                const statusMessage = getElement('statusMessage');

                if (capturedPhotoContainer) capturedPhotoContainer.classList.add('hidden');
                if (retakeBtn) retakeBtn.classList.add('hidden');
                if (uploadBtn) uploadBtn.classList.add('hidden');
                if (captureBtn) captureBtn.classList.remove('hidden');
                if (switchCameraBtn) switchCameraBtn.classList.add('hidden');
                if (statusMessage) statusMessage.textContent = '';
                capturedImageData = null;
            }

            function uploadCapturedPhoto() {
                const uploadBtn = getElement('uploadBtn');
                const statusMessage = getElement('statusMessage');

                if (!capturedImageData) {
                    if (statusMessage) {
                        statusMessage.textContent = 'No photo captured. Please capture a photo first.';
                        statusMessage.classList.remove('text-gray-600');
                        statusMessage.classList.add('text-red-600');
                    }
                    return;
                }

                // Disable upload button to prevent double clicks
                if (uploadBtn) uploadBtn.disabled = true;
                if (statusMessage) {
                    statusMessage.textContent = 'Uploading...';
                    statusMessage.classList.remove('text-red-600');
                    statusMessage.classList.add('text-gray-600');
                }

                // Get the Livewire component instance
                const component = @this;
                if (!component) {
                    console.error('Livewire component not found');
                    if (uploadBtn) uploadBtn.disabled = false;
                    return;
                }

                // Send image data to Livewire
                component.uploadAttachment(capturedImageData)
                    .then(() => {
                        // Reset on success
                        resetCamera();
                    })
                    .catch((error) => {
                        console.error('Upload error:', error);
                        if (statusMessage) {
                            statusMessage.textContent = 'Upload failed. Please try again.';
                            statusMessage.classList.remove('text-gray-600');
                            statusMessage.classList.add('text-red-600');
                        }
                    })
                    .finally(() => {
                        if (uploadBtn) uploadBtn.disabled = false;
                    });
            }

            // Attach functions to window for global access (after all functions are defined)
            window.capturePhoto = capturePhoto;
            window.retakePhoto = retakePhoto;
            window.uploadCapturedPhoto = uploadCapturedPhoto;
            window.switchCamera = switchCamera;
        </script>
    @endpush

</x-modals.modal-template>
