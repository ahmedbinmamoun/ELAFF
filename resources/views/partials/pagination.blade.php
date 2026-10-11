{{-- $paginated: Paginated --}}
@if ($paginated->lastPage() > 1)
    <nav class="mt-4 flex items-center justify-between rounded-card border border-rule bg-paper px-2 text-sm" aria-label="{{ __('public.pagination') }}">
        @if ($paginated->page > 1)
            <a rel="prev" href="{{ request()->fullUrlWithQuery(['page' => $paginated->page - 1]) }}" class="flex min-h-tap items-center gap-1 px-3 font-semibold text-brand"><x-svg-icon name="chevron-right" class="h-4 w-4" />{{ __('public.newer') }}</a>
        @else
            <span></span>
        @endif
        <span class="text-muted">{{ __('public.page_of', ['page' => $paginated->page, 'last' => $paginated->lastPage()]) }}</span>
        @if ($paginated->page < $paginated->lastPage())
            <a rel="next" href="{{ request()->fullUrlWithQuery(['page' => $paginated->page + 1]) }}" class="flex min-h-tap items-center gap-1 px-3 font-semibold text-brand">{{ __('public.older') }}<x-svg-icon name="chevron-left" class="h-4 w-4" /></a>
        @else
            <span></span>
        @endif
    </nav>
@endif
