<div>
    <button {{$attributes->merge(['class'=>"mt-2 w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white
        hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-600"])}}>
        {{ $slot }}
    </button>
</div>