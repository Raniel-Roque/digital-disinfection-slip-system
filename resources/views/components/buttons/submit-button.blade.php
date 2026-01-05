@props(['color' => 'orange', 'size' => 'default', 'fullWidth' => null, 'disabled' => false])

@php
    // Auto-detect full width based on size if not explicitly set
    if ($fullWidth === null) {
        $fullWidth = $size === 'default';
    }

    // Size presets
    $sizes = [
        'default' => 'px-3 py-2 text-sm font-semibold min-w-[120px] min-h-[44px]',
        'sm' => 'px-3 py-1.5 text-xs font-medium min-w-[100px] min-h-[36px]',
        'lg' => 'px-4 py-2.5 text-sm font-medium min-w-[140px] min-h-[48px]',
    ];

    // Color presets - full width mode (lighter colors for modals)
    $presetsFull = [
        'white' =>
            'text-gray-800 bg-[#FFF7F1] border border-gray-200 hover:bg-gray-200 hover:shadow-md hover:scale-[1.02] active:scale-[0.98] focus:ring-[#FFF7F1]',
        'orange' =>
            'text-white bg-[#EC8B18] border border-[#EC8B18] hover:bg-[#F5A647] hover:border-[#F5A647] hover:shadow-md hover:scale-[1.02] active:scale-[0.98] focus:ring-[#EC8B18]',
        'gray' =>
            'text-gray-800 bg-[#EEE9E1] border border-gray-300 hover:bg-gray-200 hover:border-gray-400 hover:shadow-md hover:scale-[1.02] active:scale-[0.98] focus:ring-[#EEE9E1]',
        'blue' =>
            'text-gray-800 bg-blue-200 border border-blue-100 hover:bg-blue-400 hover:border-blue-400 hover:shadow-md hover:scale-[1.02] active:scale-[0.98] focus:ring-blue-400',
        'red' =>
            'text-gray-800 bg-red-200 border border-red-100 hover:bg-red-400 hover:border-red-400 hover:shadow-md hover:scale-[1.02] active:scale-[0.98] focus:ring-red-400',
        'green' =>
            'text-gray-800 bg-green-200 border border-green-100 hover:bg-green-400 hover:border-green-400 hover:shadow-md hover:scale-[1.02] active:scale-[0.98] focus:ring-green-400',
    ];

    // Color presets - inline mode (darker, more vibrant for table actions)
    $presetsInline = [
        'white' => 'text-gray-800 bg-white border border-gray-300 hover:bg-gray-50 focus:ring-gray-500',
        'orange' => 'text-white bg-orange-600 hover:bg-orange-700 focus:ring-orange-500',
        'gray' => 'text-white bg-gray-600 hover:bg-gray-700 focus:ring-gray-500',
        'blue' => 'text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
        'red' => 'text-white bg-red-600 hover:bg-red-700 focus:ring-red-500',
        'green' => 'text-white bg-green-600 hover:bg-green-700 focus:ring-green-500',
    ];

    $presets = $fullWidth ? $presetsFull : $presetsInline;
    $preset = $presets[$color] ?? $presets['orange'];
    $sizeClass = $sizes[$size] ?? $sizes['default'];
    $widthClass = $fullWidth ? 'w-full' : '';
    $baseClasses = $fullWidth
        ? 'flex items-center justify-center gap-2 rounded-lg focus:ring-2 transition-all duration-200 hover:cursor-pointer cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed'
        : 'inline-flex items-center gap-1.5 rounded-lg focus:ring-2 focus:ring-offset-2 transition-colors duration-150 hover:cursor-pointer cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed min-w-[100px]';
    $disabledClasses = $disabled ? 'opacity-50 cursor-not-allowed' : '';
@endphp

@php
    $buttonAttributes = $attributes->merge([
        'class' => ($fullWidth ? "$widthClass $baseClasses $sizeClass $preset $disabledClasses" : "$baseClasses $sizeClass $preset hover:cursor-pointer $disabledClasses"),
    ]);
    if ($disabled) {
        $buttonAttributes = $buttonAttributes->merge(['disabled' => true]);
    }
@endphp

@if ($fullWidth)
    <div>
        <button {{ $buttonAttributes }}>
            {{ $slot }}
        </button>
    </div>
@else
    <button {{ $buttonAttributes }}>
        {{ $slot }}
    </button>
@endif
