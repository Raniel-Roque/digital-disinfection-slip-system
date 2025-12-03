@props(['show', 'file'])

<x-modals.modal-template :show="$show" title="Attachment Preview" max-width="max-w-4xl">

    @php
        $fileUrl = Storage::url($file);
        $extension = strtolower(pathinfo($file ?? '', PATHINFO_EXTENSION));
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    @endphp

    @if (in_array($extension, $imageExtensions))
        {{-- IMAGE PREVIEW ONLY --}}
        <img src="{{ $fileUrl }}" class="border shadow-md max-h-[80vh] object-contain mx-auto rounded-lg"
            alt="Attachment Preview">
    @else
        {{-- NO PREVIEW — ONLY LINK --}}
        <p class="text-sm text-gray-600 text-center p-4">
            This file type cannot be previewed.<br>
            <a href="{{ $fileUrl }}" target="_blank" class="text-orange-500 font-semibold underline">
                Download attachment
            </a>
        </p>
    @endif

    <x-slot name="footer">
        <x-buttons.submit-button wire:click="closeAttachmentModal" color="gray">
            Close
        </x-buttons.submit-button>
    </x-slot>

</x-modals.modal-template>
