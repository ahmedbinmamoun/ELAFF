@extends('layouts.app')

@php($card = $article->card)
@php($url = route('article.show', ['id' => $card->id, 'slug' => $card->slug]))

@section('title', $article->seoTitle ?? $card->title)
@section('description', $article->seoDescription ?? $card->excerpt ?? $article->subtitle ?? __('site.tagline'))
@section('canonical', $article->canonicalUrl ?? $url)

@section('content')
    <nav class="mb-3 flex items-center gap-1 text-sm text-muted" aria-label="breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-brand">{{ __('public.home') }}</a>
        <x-svg-icon name="chevron-left" class="h-4 w-4" />
        <a href="{{ route('category.show', $card->category->slug) }}" class="font-semibold text-brand">{{ $card->category->name }}</a>
    </nav>

    <x-with-aside :most-read="$mostRead">
        <article class="rounded-card border border-rule bg-paper p-4 sm:p-6">
            <header>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('category.show', $card->category->slug) }}"><x-badge tone="brand">{{ $card->category->name }}</x-badge></a>
                    @if ($card->kicker)<x-badge>{{ $card->kicker }}</x-badge>@endif
                </div>
                <h1 class="mt-3 font-serif text-2xl font-bold leading-snug sm:text-3xl lg:text-4xl">{{ $card->title }}</h1>
                @if ($article->subtitle)
                    <p class="mt-3 text-lg leading-relaxed text-muted">{{ $article->subtitle }}</p>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 border-y border-rule py-3 text-sm">
                    @if ($card->authors !== [])
                        <div class="flex items-center gap-2">
                            @foreach ($card->authors as $author)
                                <a href="{{ route('author.show', $author->slug) }}" class="flex min-h-tap items-center gap-2 font-semibold hover:text-accent">
                                    @if ($author->photoPath)
                                        <img src="{{ \Elaf\Shared\Presentation\PublicMedia::url($author->photoPath) }}" alt="" width="32" height="32" loading="lazy" class="h-8 w-8 rounded-full object-cover">
                                    @endif
                                    {{ $author->name }}
                                </a>
                            @endforeach
                            @if ($guestLabel)
                                <span class="rounded-badge bg-canvas px-2 py-0.5 text-xs text-muted">{{ __('public.guest_writer') }}</span>
                            @endif
                        </div>
                    @endif
                    @if ($article->dateline)<span class="text-muted">{{ $article->dateline }}</span>@endif
                    <x-date :at="$card->publishedAt" :time="true" />
                    <span class="text-xs text-muted">{{ trans_choice('public.reading_time', $card->readingMinutes, ['minutes' => $card->readingMinutes]) }}</span>
                    <x-favorite-button :id="$card->id" class="ms-auto" />
                </div>
            </header>

            @if ($card->image)
                <figure class="mt-5">
                    <div class="aspect-[16/9] overflow-hidden rounded-card bg-rule">
                        <x-img :path="$card->image->path" :alt="$card->image->caption ?? $card->title" width="1280" height="720" eager sizes="(min-width: 1024px) 50vw, 100vw" />
                    </div>
                    @if ($card->image->caption || $card->image->credit)
                        <figcaption class="mt-2 text-sm text-muted">{{ $card->image->caption }}@if ($card->image->credit)<span class="ms-1">({{ $card->image->credit }})</span>@endif</figcaption>
                    @endif
                </figure>
            @endif

            <div class="mt-5">@include('partials.share-bar', ['url' => $url, 'title' => $card->title])</div>

            <div class="article-body mt-6" x-ignore>
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

            @if ($article->print)
                <a href="{{ route('issues.show', $article->print->issueNumber) }}#page-{{ $article->print->page }}"
                   class="mt-3 flex min-h-tap items-center gap-2 rounded-card bg-canvas px-3 text-sm font-semibold text-brand hover:text-accent">
                    <x-svg-icon name="newspaper" class="h-5 w-5 shrink-0 text-accent" />
                    {{ __('public.issue.printed_in', ['number' => $article->print->issueNumber, 'page' => $article->print->page]) }}
                </a>
            @endif

            @if ($article->series)
                @php($series = $article->series)
                <nav class="mt-6 rounded-card bg-canvas p-4" aria-label="{{ __('public.series_title', ['name' => $series->title]) }}">
                    <p class="text-sm"><a href="{{ route('series.show', $series->slug) }}" class="font-bold text-brand">{{ $series->title }}</a>
                        <span class="text-muted">— {{ __('public.series_part', ['part' => $series->part, 'parts' => $series->parts]) }}</span></p>
                    <div class="mt-2 grid gap-2 text-sm sm:grid-cols-2">
                        @if ($series->previous)
                            <a rel="prev" href="{{ route('article.show', ['id' => $series->previous->id, 'slug' => $series->previous->slug]) }}" class="flex min-h-tap items-center gap-1 hover:text-accent"><x-svg-icon name="chevron-right" class="h-4 w-4 shrink-0" />{{ __('public.series_previous') }}: {{ $series->previous->title }}</a>
                        @endif
                        @if ($series->next)
                            <a rel="next" href="{{ route('article.show', ['id' => $series->next->id, 'slug' => $series->next->slug]) }}" class="flex min-h-tap items-center gap-1 sm:col-start-2 sm:justify-end hover:text-accent">{{ __('public.series_next') }}: {{ $series->next->title }}<x-svg-icon name="chevron-left" class="h-4 w-4 shrink-0" /></a>
                        @endif
                    </div>
                </nav>
            @endif

            @if ($article->tags !== [])
                <ul class="mt-6 flex flex-wrap gap-2 text-sm" aria-label="{{ __('public.tags') }}">
                    @foreach ($article->tags as $tag)
                        <li><a href="{{ route('tag.show', $tag->slug) }}" class="flex min-h-tap items-center rounded-full border border-rule px-4 hover:border-accent hover:text-accent">{{ $tag->name }}</a></li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-6 border-t border-rule pt-4">@include('partials.share-bar', ['url' => $url, 'title' => $card->title])</div>
        </article>

        @if ($related !== [])
            <section class="defer-render mt-6">
                <x-section-title>{{ __('public.related') }}</x-section-title>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($related as $item)
                        <x-card.medium :card="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </x-with-aside>
@endsection
