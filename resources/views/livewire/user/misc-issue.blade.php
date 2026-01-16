<div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 lg:p-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Submit an Issue</h1>
                <p class="text-gray-600 text-sm mt-1">Submit any issues or concerns you have encountered</p>
            </div>

            @if ($showSuccess)
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm font-medium text-green-800">
                            Your issue has been submitted successfully. It will be reviewed by administrators.
                        </p>
                    </div>
                </div>
            @endif

            <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
<div>
                        <p class="text-sm font-semibold text-yellow-800 mb-1">Important</p>
                        <p class="text-sm text-yellow-700">
                            Please provide a detailed description of the issue or concern. This issue will be reviewed by administrators.
                        </p>
                    </div>
                </div>
            </div>

            <form wire:submit="submitIssue">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Description <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model="description" rows="8"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm"
                        placeholder="Please describe the issue or concern in detail..."></textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Minimum 10 characters required. Maximum 1000 characters.</p>
                </div>

                {{-- Mobile Layout --}}
                <div class="flex flex-col gap-3 md:hidden">
                    <button type="submit"
                        class="w-full px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Submit Issue
                    </button>
                    <a href="{{ route('user.dashboard') }}"
                        class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 text-center">
                        Cancel
                    </a>
                </div>

                {{-- Desktop Layout --}}
                <div class="hidden md:flex justify-end gap-3">
                    <a href="{{ route('user.dashboard') }}"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Submit Issue
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
