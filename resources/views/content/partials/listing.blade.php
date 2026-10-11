{{-- $articles: Paginated<ArticleCard> --}}
@if ($articles->items === [])
    <p class="mt-8 text-muted">{{ __('public.no_articles') }}</p>
@else
    <div class="mt-6 grid gap-x-6 gap-y-8 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($articles->items as $card)
            @include('partials.card', ['card' => $card, 'variant' => 'standard', 'eager' => $loop->first && $articles->page === 1])
        @endforeach
    </div>
    @include('partials.pagination', ['paginated' => $articles])
@endif
