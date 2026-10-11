{{-- $articles: Paginated<ArticleCard> --}}
@if ($articles->items === [])
    <p class="rounded-card border border-rule bg-paper p-6 text-muted">{{ __('public.no_articles') }}</p>
@else
    @if ($articles->page === 1)
        <div class="mb-4"><x-card.medium :card="$articles->items[0]" :eager="true" /></div>
    @endif
    <div class="divide-y divide-rule rounded-card border border-rule bg-paper px-4">
        @foreach ($articles->page === 1 ? array_slice($articles->items, 1) : $articles->items as $card)
            <x-card.list :card="$card" />
        @endforeach
    </div>
    @include('partials.pagination', ['paginated' => $articles])
@endif
