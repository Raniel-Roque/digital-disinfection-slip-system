@props(['href' => '#'])

<a 
    href="{{ $href }}"
    {{ $attributes->merge([
        'class' => '
            inline-flex
            items-center
            rounded-full
            px-4 py-2
            text-sm font-semibold
            text-gray-800
            bg-[#FFF7F1]
            hover:bg-gray-200
            hover:shadow-md
            hover:scale-[1.02]
            active:scale-[0.98]
            focus:ring-2
            focus:ring-[#FFF7F1]
            transition-all
            duration-200
            cursor-pointer
        '
    ]) }}
>
    {{ $slot }}
</a>
