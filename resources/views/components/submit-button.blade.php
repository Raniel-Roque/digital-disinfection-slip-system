@props(['color' => 'orange'])

@php
    $presets = [
        'white' =>
            'text-gray-800 bg-[#FFF7F1] hover:bg-gray-200 hover:shadow-md hover:scale-[1.02] active:scale-[0.98] focus:ring-[#FFF7F1]',
        'orange' =>
            'text-white bg-[#EC8B18] border border-[#EC8B18] hover:bg-[#F5A647] hover:border-[#F5A647] hover:shadow-md hover:scale-[1.02] active:scale-[0.98] focus:ring-[#EC8B18]',
        'gray' =>
            'text-gray-800 bg-[#EEE9E1] border border-gray-300 hover:bg-gray-200 hover:border-gray-400 hover:shadow-md hover:scale-[1.02] active:scale-[0.98] focus:ring-[#EEE9E1]',
    ];

    $preset = $presets[$color] ?? $presets['orange'];
@endphp

<div>
    <button
        {{ $attributes->merge([
                'class' => "
                        w-full
                        rounded-full
                        px-3 py-2
                        text-sm font-semibold
                        {$preset}
                        focus:ring-2
                        transition-all
                        duration-200
                        hover:cursor-pointer
                    ",
            ])->merge(['disabled' => false]) }}>
        {{ $slot }}
    </button>
</div>
