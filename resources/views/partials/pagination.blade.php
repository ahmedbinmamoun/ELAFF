{{-- $paginated: Paginated --}}
@if ($paginated->lastPage() > 1)
    <nav class="mt-8 flex items-center justify-between border-t border-rule pt-4 text-sm" aria-label="{{ __('public.pagination') }}">
        @if ($paginated->page > 1)
            <a rel="prev" href="{{ request()->fullUrlWithQuery(['page' => $paginated->page - 1]) }}" class="font-semibold text-brand">→ {{ __('public.newer') }}</a>
        @else
            <span></span>
        @endif
        <span class="text-muted">{{ __('public.page_of', ['page' => $paginated->page, 'last' => $paginated->lastPage()]) }}</span>
        @if ($paginated->page < $paginated->lastPage())
            <a rel="next" href="{{ request()->fullUrlWithQuery(['page' => $paginated->page + 1]) }}" class="font-semibold text-brand">{{ __('public.older') }} ←</a>
        @else
            <span></span>
        @endif
    </nav>
@endif
