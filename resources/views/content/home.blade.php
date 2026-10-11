@extends('layouts.app')

@section('canonical', route('home'))

@section('content')
    @if ($breaking !== [])
        <section class="mt-4 flex items-stretch overflow-hidden rounded border border-accent text-sm" aria-label="{{ __('public.breaking') }}">
            <h2 class="shrink-0 bg-accent px-3 py-2 font-bold text-white">{{ __('public.breaking') }}</h2>
            <ul class="nav-scroll flex gap-6 overflow-x-auto whitespace-nowrap px-3 py-2">
                @foreach ($breaking as $item)
                    <li><a href="{{ route('article.show', ['id' => $item->id, 'slug' => $item->slug]) }}" class="font-semibold hover:text-accent">{{ $item->title }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($lead)
        <section class="mt-6 grid gap-6 lg:grid-cols-3" aria-label="{{ __('public.home') }}">
            <div class="lg:col-span-2">
                @include('partials.card', ['card' => $lead, 'variant' => 'lead', 'eager' => true])
            </div>
            <div class="grid content-start gap-5 sm:grid-cols-2 lg:grid-cols-1">
                @foreach ($secondary as $card)
                    @include('partials.card', ['card' => $card, 'variant' => $loop->first ? 'standard' : 'compact'])
                @endforeach
            </div>
        </section>
    @else
        <p class="mt-10 text-center text-muted">{{ __('public.no_articles') }}</p>
    @endif

    @if ($latest !== [])
        <section class="mt-10">
            <h2 class="section-title">{{ __('public.latest') }}</h2>
            <div class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($latest as $card)
                    @include('partials.card', ['card' => $card, 'variant' => 'compact'])
                @endforeach
            </div>
        </section>
    @endif

    @if ($columnists !== [])
        <section class="mt-10">
            <h2 class="section-title"><a href="{{ route('authors.index') }}">{{ __('public.columnists') }}</a></h2>
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($columnists as $item)
                    @include('content.partials.columnist', ['item' => $item])
                @endforeach
            </ul>
        </section>
    @endif

    @foreach ($sections as $section)
        <section class="mt-10">
            <h2 class="section-title"><a href="{{ route('category.show', $section->category->slug) }}">{{ $section->category->name }}</a></h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($section->articles as $card)
                    @include('partials.card', ['card' => $card, 'variant' => $loop->first ? 'standard' : 'compact'])
                @endforeach
            </div>
        </section>
    @endforeach
@endsection
