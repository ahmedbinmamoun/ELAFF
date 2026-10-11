@extends('layouts.app')

@php($card = $article->card)
@php($url = route('article.show', ['id' => $card->id, 'slug' => $card->slug]))

@section('title', $article->seoTitle ?? $card->title)
@section('description', $article->seoDescription ?? $card->excerpt ?? $article->subtitle ?? __('site.tagline'))
@section('canonical', $article->canonicalUrl ?? $url)

@section('content')
    <article class="mx-auto mt-6 max-w-3xl">
        <nav class="text-sm text-muted" aria-label="breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-brand">{{ __('public.home') }}</a>
            <span aria-hidden="true">›</span>
            <a href="{{ route('category.show', $card->category->slug) }}" class="font-semibold text-brand">{{ $card->category->name }}</a>
        </nav>

        <header class="mt-3">
            @if ($card->kicker)
                <p class="kicker">{{ $card->kicker }}</p>
            @endif
            <h1 class="font-serif text-3xl font-bold leading-tight sm:text-4xl">{{ $card->title }}</h1>
            @if ($article->subtitle)
                <p class="mt-3 text-lg text-muted">{{ $article->subtitle }}</p>
            @endif

            <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 border-y border-rule py-3 text-sm text-muted">
                @include('partials.byline', ['card' => $card, 'guest' => $guestLabel])
                @if ($article->dateline)
                    <span>{{ $article->dateline }}</span>
                @endif
                <time datetime="{{ $card->publishedAt->format(DATE_ATOM) }}">{{ \Elaf\Shared\Presentation\ArabicDate::dateTime($card->publishedAt) }}</time>
                <span>{{ trans_choice('public.reading_time', $card->readingMinutes, ['minutes' => $card->readingMinutes]) }}</span>
            </div>
        </header>

        @if ($card->image)
            <figure class="mt-5">
                @include('partials.image', ['image' => $card->image, 'alt' => $card->image->caption ?? $card->title, 'eager' => true])
                @if ($card->image->caption || $card->image->credit)
                    <figcaption class="mt-2 text-sm text-muted">
                        {{ $card->image->caption }}
                        @if ($card->image->credit)
                            <span class="ms-1">({{ $card->image->credit }})</span>
                        @endif
                    </figcaption>
                @endif
            </figure>
        @endif

        <div class="mt-5">@include('partials.share-bar', ['url' => $url, 'title' => $card->title])</div>

        <div class="article-body mt-6">
            {!! $article->bodyHtml !!}
        </div>

        <p class="mt-6 text-sm text-muted">
            {{ __('public.source') }}:
            @if ($article->sourceUrl)
                <a href="{{ $article->sourceUrl }}" rel="nofollow noopener" class="text-brand underline">{{ $article->sourceName }}</a>
            @else
                {{ $article->sourceName }}
            @endif
        </p>

        @if ($article->series)
            @php($series = $article->series)
            <nav class="mt-8 rounded border border-rule p-4" aria-label="{{ __('public.series_title', ['name' => $series->title]) }}">
                <p class="text-sm text-muted">
                    <a href="{{ route('series.show', $series->slug) }}" class="font-bold text-brand">{{ $series->title }}</a>
                    — {{ __('public.series_part', ['part' => $series->part, 'parts' => $series->parts]) }}
                </p>
                <div class="mt-3 flex justify-between gap-4 text-sm">
                    @if ($series->previous)
                        <a rel="prev" href="{{ route('article.show', ['id' => $series->previous->id, 'slug' => $series->previous->slug]) }}" class="hover:text-brand">→ {{ __('public.series_previous') }}: {{ $series->previous->title }}</a>
                    @else
                        <span></span>
                    @endif
                    @if ($series->next)
                        <a rel="next" href="{{ route('article.show', ['id' => $series->next->id, 'slug' => $series->next->slug]) }}" class="text-start hover:text-brand">{{ __('public.series_next') }}: {{ $series->next->title }} ←</a>
                    @endif
                </div>
            </nav>
        @endif

        @if ($article->tags !== [])
            <ul class="mt-6 flex flex-wrap gap-2 text-sm" aria-label="{{ __('public.tags') }}">
                @foreach ($article->tags as $tag)
                    <li><a href="{{ route('tag.show', $tag->slug) }}" class="rounded-full border border-rule px-3 py-1 hover:border-brand hover:text-brand">{{ $tag->name }}</a></li>
                @endforeach
            </ul>
        @endif

        <div class="mt-8">@include('partials.share-bar', ['url' => $url, 'title' => $card->title])</div>
    </article>

    @if ($related !== [])
        <section class="mx-auto mt-12 max-w-3xl">
            <h2 class="section-title">{{ __('public.related') }}</h2>
            <div class="grid gap-6 sm:grid-cols-2">
                @foreach ($related as $item)
                    @include('partials.card', ['card' => $item, 'variant' => 'standard'])
                @endforeach
            </div>
        </section>
    @endif
@endsection
