@props(['paginator'])

@if ($paginator->lastPage() > 1)
    <div>

        {{-- Wrapper responsive layout --}}
        <div class="flex flex-col-reverse md:flex-row md:items-center md:justify-between gap-3">

            {{-- Showing text --}}
            <div class="text-sm text-gray-600 text-center md:text-left">
                Showing
                {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
                of
                {{ $paginator->total() }} entries
            </div>

            {{-- Pagination --}}
            <nav class="flex justify-center md:justify-end space-x-1" aria-label="Pagination">

                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <button
                        class="p-2.5 min-w-10 inline-flex justify-center items-center rounded-full text-gray-400 bg-gray-50 cursor-not-allowed">«</button>
                @else
                    <button wire:click="previousPage"
                        class="p-2.5 min-w-10 inline-flex justify-center items-center rounded-full text-gray-700 hover:bg-gray-100 hover:cursor-pointer cursor-pointer">«</button>
                @endif

                @php
                    $current = $paginator->currentPage();
                    $last = $paginator->lastPage();

                    if ($last <= 3) {
                        $start = 1;
                        $end = $last;
                    } else {
                        if ($current === 1) {
                            $start = 1;
                            $end = 3;
                        } elseif ($current === $last) {
                            $start = $last - 2;
                            $end = $last;
                        } else {
                            $start = $current - 1;
                            $end = $current + 1;
                        }
                    }
                @endphp

                {{-- Page numbers --}}
                @for ($i = $start; $i <= $end; $i++)
                    @if ($i === $current)
                        <button
                            class="min-w-10 py-2.5 px-4 rounded-full bg-gray-100 text-gray-800 text-sm">{{ $i }}</button>
                    @else
                        <button wire:click="gotoPage({{ $i }})"
                            class="min-w-10 py-2.5 px-4 rounded-full hover:bg-gray-100 text-gray-800 text-sm hover:cursor-pointer cursor-pointer">
                            {{ $i }}
                        </button>
                    @endif
                @endfor

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <button wire:click="nextPage"
                        class="p-2.5 min-w-10 inline-flex justify-center items-center rounded-full text-gray-700 hover:bg-gray-100 hover:cursor-pointer cursor-pointer">»</button>
                @else
                    <button
                        class="p-2.5 min-w-10 inline-flex justify-center items-center rounded-full text-gray-400 bg-gray-50 cursor-not-allowed">»</button>
                @endif

            </nav>
        </div>
    </div>
@endif
