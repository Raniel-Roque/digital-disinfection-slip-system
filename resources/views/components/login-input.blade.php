<div>
    <label class="block text-sm font-medium text-gray-700">
        {{ $slot }}
    </label>
    <input {{ $attributes->merge([
        'class' =>
            'mt-1 w-full rounded-md bg-white
            border border-gray-300
            px-3 py-2 text-sm text-gray-900
            placeholder-gray-400
            focus:ring-2 focus:ring-indigo-600'
    ]) }}>
</div>