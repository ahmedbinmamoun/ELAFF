@extends('layouts.app')

@php($featured = $issues->page === 1 && $year === null && $issues->items !== [] ? $issues->items[0] : null)
@php($grid = $featured ? array_slice($issues->items, 1) : $issues->items)

@section('title', $year ? __('public.issues_page.title_year', ['year' => $year]) : __('public.issues_page.title'))
@section('description', __('public.issues_page.intro'))
@section('canonical', route('issues.index', array_filter(['year' => $year, 'page' => $issues->page > 1 ? $issues->page : null])))

@section('content')
    <x-page-header :title="$year ? __('public.issues_page.title_year', ['year' => $year]) : __('public.issues_page.title')" icon="newspaper">
        <p class="mt-2 text-muted">{{ __('public.issues_page.intro') }}</p>
    </x-page-header>

    @if ($years !== [])
        <nav class="nav-scroll mb-4 flex gap-2 overflow-x-auto" aria-label="{{ __('public.issues_page.years') }}">
            <a href="{{ route('issues.index') }}" @if ($year === null) aria-current="page" @endif
               class="flex min-h-tap shrink-0 items-center rounded-badge border px-4 text-sm font-semibold {{ $year === null ? 'border-brand bg-brand text-white' : 'border-rule bg-paper text-brand hover:border-brand' }}">{{ __('public.issues_page.all_years') }}</a>
            @foreach ($years as $y)
                <a href="{{ route('issues.index', ['year' => $y]) }}" @if ($year === $y) aria-current="page" @endif
                   class="flex min-h-tap shrink-0 items-center rounded-badge border px-4 text-sm font-semibold {{ $year === $y ? 'border-brand bg-brand text-white' : 'border-rule bg-paper text-brand hover:border-brand' }}">{{ $y }}</a>
            @endforeach
        </nav>
    @endif

    @if ($issues->items === [])
        <p class="rounded-card border border-rule bg-paper p-6 text-muted">{{ __('public.issues_page.empty') }}</p>
    @else
        @if ($featured)
            <section class="mb-6 grid gap-4 rounded-card border border-rule bg-paper p-4 sm:grid-cols-[minmax(0,14rem)_1fr] sm:p-5" aria-labelledby="latest-issue">
                <a href="{{ route('issues.show', $featured->number) }}" class="mx-auto block w-48 overflow-hidden rounded-badge bg-rule shadow-sm sm:w-auto" style="aspect-ratio: {{ $featured->coverWidth }} / {{ $featured->coverHeight }}">
                    <img src="{{ \Elaf\Publishing\Presentation\Http\IssueMedia::url($featured->coverThumbPath) }}" alt="{{ __('public.issue.page_alt', ['number' => $featured->number, 'page' => 1]) }}"
                         width="320" height="{{ (int) round(320 * $featured->coverHeight / max(1, $featured->coverWidth)) }}" fetchpriority="high" decoding="async" class="h-full w-full object-cover">
                </a>
                <div class="flex flex-col justify-center gap-3">
                    <x-badge class="self-start">{{ __('public.issues_page.latest') }}</x-badge>
                    <h2 id="latest-issue" class="font-serif text-2xl font-bold text-brand sm:text-3xl">{{ __('public.issue.number', ['number' => $featured->number]) }}</h2>
                    @if ($featured->title)<p class="text-lg">{{ $featured->title }}</p>@endif
                    <p class="flex flex-wrap items-center gap-3 text-sm text-muted">
                        <x-date :at="$featured->publishedOn" class="!text-sm" />
                        <span>{{ __('public.issue.pages', ['count' => $featured->pageCount]) }}</span>
                    </p>
                    @include('publishing.partials.actions', ['issue' => $featured])
                </div>
            </section>
        @endif

        @if ($grid !== [])
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                @foreach ($grid as $issue)
                    @include('publishing.partials.card', ['issue' => $issue])
                @endforeach
            </div>
        @endif

        @include('partials.pagination', ['paginated' => $issues])
    @endif
@endsection
