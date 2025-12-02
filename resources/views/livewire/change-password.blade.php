<div class="w-full max-w-md">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8">
        <h2 class="text-2xl font-semibold text-gray-900 mb-6 text-center">Change Password</h2>

        @if (session('password_changed') || $showSuccess)
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-800">Password changed successfully.</p>
            </div>
        @endif

        <form wire:submit="updatePassword">
            <div class="space-y-4">
                <div>
                    <label for="currentPassword" class="block text-sm font-medium text-gray-700 mb-2">
                        Current Password
                    </label>
                    <input type="password" wire:model="currentPassword" id="currentPassword" required
                        autocomplete="current-password"
                        class="w-full px-4 py-2 border {{ $errors->has('currentPassword') ? 'border-red-300' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-[#EC8B18] focus:border-[#EC8B18] outline-none transition"
                        placeholder="Enter your current password">
                    @error('currentPassword')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="newPassword" class="block text-sm font-medium text-gray-700 mb-2">
                        New Password
                    </label>
                    <input type="password" wire:model="newPassword" id="newPassword" required minlength="8"
                        autocomplete="new-password"
                        class="w-full px-4 py-2 border {{ $errors->has('newPassword') ? 'border-red-300' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-[#EC8B18] focus:border-[#EC8B18] outline-none transition"
                        placeholder="Enter new password (min. 8 characters)">
                    <p class="mt-1 text-xs text-gray-500">Minimum 8 characters. No complexity requirements.</p>
                    @error('newPassword')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="newPasswordConfirmation" class="block text-sm font-medium text-gray-700 mb-2">
                        Confirm New Password
                    </label>
                    <input type="password" wire:model="newPasswordConfirmation" id="newPasswordConfirmation" required
                        minlength="8" autocomplete="new-password"
                        class="w-full px-4 py-2 border {{ $errors->has('newPasswordConfirmation') ? 'border-red-300' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-[#EC8B18] focus:border-[#EC8B18] outline-none transition"
                        placeholder="Confirm new password">
                    @error('newPasswordConfirmation')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3">
                    <a href="{{ route(auth()->user()->dashboardRoute()) }}"
                        class="flex-1 px-4 py-2 text-sm font-semibold text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors text-center">
                        Cancel
                    </a>
                    <button type="submit"
                        class="flex-1 px-4 py-2 text-sm font-semibold text-white bg-[#EC8B18] rounded-lg hover:bg-[#d67a15] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>Change Password</span>
                        <span wire:loading>Changing...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
