<div class="min-h-screen bg-gray-50 p-4 sm:p-6" wire:poll>
    <div class="max-w-6xl mx-auto">
        {{-- Settings Form Card --}}
        <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
            {{-- Header --}}
            <div class="px-6 py-5 bg-gray-50 border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
                <p class="text-gray-600 text-sm mt-1">Manage system configuration and preferences</p>
            </div>

            <form wire:submit.prevent="updateSettings">
                <div class="p-6">
                    {{-- 2 Column Grid Layout --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        {{-- Left Column --}}
                        <div class="space-y-6">
                            {{-- Attachment Retention Days --}}
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                                <label for="attachment_retention_days"
                                    class="block text-sm font-semibold text-gray-800 mb-3">
                                    Attachment Retention
                                </label>
                                <div class="flex items-center gap-3 mb-2">
                                    <input type="number" id="attachment_retention_days"
                                        wire:model="attachment_retention_days" min="1" max="365"
                                        class="block w-24 px-3 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="30">
                                    <span class="text-sm font-medium text-gray-700">days</span>
                                </div>
                                <p class="text-xs text-gray-600 leading-relaxed">
                                    Uploads (attachments) will be automatically deleted after this many days.
                                </p>
                                @error('attachment_retention_days')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Resolved Reports Retention Months --}}
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                                <label for="resolved_reports_retention_months"
                                    class="block text-sm font-semibold text-gray-800 mb-3">
                                    Resolved Reports Retention
                                </label>
                                <div class="flex items-center gap-3 mb-2">
                                    <input type="number" id="resolved_reports_retention_months"
                                        wire:model="resolved_reports_retention_months" min="1" max="120"
                                        class="block w-24 px-3 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="6">
                                    <span class="text-sm font-medium text-gray-700">months</span>
                                </div>
                                <p class="text-xs text-gray-600 leading-relaxed">
                                    Resolved reports older than this period will be automatically deleted. Default is 6
                                    months.
                                </p>
                                @error('resolved_reports_retention_months')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Log Retention Months --}}
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                                <label for="log_retention_months"
                                    class="block text-sm font-semibold text-gray-800 mb-3">
                                    Log Retention
                                </label>
                                <div class="flex items-center gap-3 mb-2">
                                    <input type="number" id="log_retention_months" wire:model="log_retention_months"
                                        min="1" max="120"
                                        class="block w-24 px-3 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="6">
                                    <span class="text-sm font-medium text-gray-700">months</span>
                                </div>
                                <p class="text-xs text-gray-600 leading-relaxed">
                                    Audit trail logs older than this period will be automatically deleted. Default is 6
                                    months.
                                </p>
                                @error('log_retention_months')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Right Column --}}
                        <div class="space-y-6">
                            {{-- Default Guard Password --}}
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                                <label for="default_guard_password"
                                    class="block text-sm font-semibold text-gray-800 mb-3">
                                    Default Password
                                </label>
                                <input type="text" id="default_guard_password" wire:model="default_guard_password"
                                    class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-900 mb-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="brookside25">
                                <p class="text-xs text-gray-600 leading-relaxed">
                                    This password will be used when creating new guards or resetting guard passwords.
                                </p>
                                @error('default_guard_password')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Default Location Logo --}}
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                                <label class="block text-sm font-semibold text-gray-800 mb-3">
                                    Default Location Logo
                                </label>

                                {{-- File Upload Section --}}
                                <div class="space-y-3">
                                    <label
                                        class="cursor-pointer inline-flex items-center justify-center w-full px-4 py-2.5 bg-white border-2 border-dashed border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:border-blue-400 hover:bg-blue-50 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        Choose Image
                                        <input type="file" wire:model="default_logo_file" class="hidden"
                                            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                    </label>

                                    {{-- File Info --}}
                                    @if ($default_logo_file)
                                        <div
                                            class="flex items-center justify-between bg-white rounded-md px-3 py-2 border border-gray-200">
                                            <p class="text-sm text-gray-700 truncate flex-1"
                                                title="{{ $default_logo_file->getClientOriginalName() }}">
                                                {{ $default_logo_file->getClientOriginalName() }}
                                            </p>
                                            <button wire:click="clearLogo" type="button"
                                                class="ml-2 text-xs text-red-600 hover:text-red-800 font-medium">
                                                Clear
                                            </button>
                                        </div>
                                    @elseif ($this->defaultLogoPath)
                                        <div class="bg-white rounded-md px-3 py-2 border border-gray-200">
                                            <p class="text-sm text-gray-700 truncate"
                                                title="{{ $this->defaultLogoPath }}">
                                                Current: {{ basename($this->defaultLogoPath) }}
                                            </p>
                                        </div>
                                    @endif

                                    {{-- Logo Preview --}}
                                    <div
                                        class="flex items-center justify-center bg-white rounded-lg p-3 border border-gray-200">
                                        @if ($default_logo_file)
                                            <img src="{{ $default_logo_file->temporaryUrl() }}" alt="Logo preview"
                                                class="max-w-full max-h-28 object-contain">
                                        @elseif ($this->defaultLogoPath)
                                            <img src="{{ asset('storage/' . $this->defaultLogoPath) }}"
                                                alt="Current logo" class="max-w-full max-h-28 object-contain">
                                        @else
                                            <div
                                                class="w-full h-28 flex items-center justify-center bg-gray-50 rounded-md">
                                                <span class="text-xs text-gray-400">No image selected</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @error('default_logo_file')
                                    <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span>
                                @enderror
                                <p class="text-xs text-gray-600 mt-3 leading-relaxed">
                                    This logo will be used when a location doesn't have a custom logo. Supported
                                    formats: JPEG, PNG, GIF, WebP (Max 2MB)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Footer --}}
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button type="submit" wire:loading.attr="disabled" wire:target="updateSettings"
                        class="px-6 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm hover:shadow-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        @if (!$this->hasChanges) disabled @endif>
                        <span wire:loading.remove wire:target="updateSettings">Save Settings</span>
                        <span wire:loading wire:target="updateSettings" class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
