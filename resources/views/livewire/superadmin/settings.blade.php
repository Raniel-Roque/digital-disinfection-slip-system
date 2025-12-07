<div class="min-h-screen bg-gray-50 p-6" wire:poll>
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
            <p class="text-gray-600 text-sm mt-1">Manage system configuration and preferences</p>
        </div>

        {{-- Settings Form Card --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <form wire:submit.prevent="updateSettings">
                <div class="p-6 space-y-6">
                    {{-- Attachment Retention Days --}}
                    <div>
                        <label for="attachment_retention_days" class="block text-sm font-medium text-gray-700 mb-2">
                            Attachment Retention Days
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="number" id="attachment_retention_days" wire:model="attachment_retention_days"
                                min="1" max="365"
                                class="block w-32 px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="30">
                            <span class="text-sm text-gray-600">days</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Uploads (attachments) will be automatically deleted after this many days.
                        </p>
                        @error('attachment_retention_days')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Divider --}}
                    <div class="border-t border-gray-200"></div>

                    {{-- Default Guard Password --}}
                    <div>
                        <label for="default_guard_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Default Guard Password
                        </label>
                        <input type="text" id="default_guard_password" wire:model="default_guard_password"
                            class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="brookside25">
                        <p class="mt-1 text-xs text-gray-500">
                            This password will be used when creating new guards or resetting guard passwords.
                        </p>
                        @error('default_guard_password')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Divider --}}
                    <div class="border-t border-gray-200"></div>

                    {{-- Default Location Logo --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Default Location Logo
                        </label>

                        {{-- Row 1: Choose File Button + Filename | Image Preview --}}
                        <div class="grid grid-cols-2 gap-4 mb-3">
                            <div>
                                <label
                                    class="cursor-pointer inline-flex items-center w-full justify-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                    Choose Image
                                    <input type="file" wire:model="default_logo_file" class="hidden"
                                        accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                </label>
                                @if ($default_logo_file)
                                    <p class="mt-2 text-sm text-gray-600 truncate"
                                        title="{{ $default_logo_file->getClientOriginalName() }}">
                                        {{ $default_logo_file->getClientOriginalName() }}
                                    </p>
                                    <button wire:click="clearLogo" type="button"
                                        class="mt-1 text-xs text-red-600 hover:text-red-800">
                                        Clear
                                    </button>
                                @elseif ($this->defaultLogoPath)
                                    <p class="mt-2 text-sm text-gray-600 truncate" title="{{ $this->defaultLogoPath }}">
                                        Current: {{ basename($this->defaultLogoPath) }}
                                    </p>
                                @endif
                            </div>
                            <div class="flex items-center justify-center">
                                @if ($default_logo_file)
                                    <img src="{{ $default_logo_file->temporaryUrl() }}" alt="Logo preview"
                                        class="max-w-full max-h-32 object-contain rounded-lg border border-gray-200">
                                @elseif ($this->defaultLogoPath)
                                    <img src="{{ asset('storage/' . $this->defaultLogoPath) }}" alt="Current logo"
                                        class="max-w-full max-h-32 object-contain rounded-lg border border-gray-200">
                                @else
                                    <div
                                        class="w-full h-32 flex items-center justify-center bg-gray-50 border border-gray-200 rounded-lg">
                                        <span class="text-xs text-gray-400">No image selected</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @error('default_logo_file')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                        <p class="text-xs text-gray-500">
                            This logo will be used when a location doesn't have a custom logo. Supported formats: JPEG,
                            PNG, GIF, WebP (Max 2MB)
                        </p>
                    </div>
                </div>

                {{-- Form Footer --}}
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
