@extends('layouts.app')

@section('title', __('public.series_title', ['name' => $series->title]))
@section('description', $series->description ?? __('site.tagline'))
@section('canonical', route('series.show', $series->slug))

@section('content')
    <header class="mt-6 border-b-2 border-brand pb-3">
        <h1 class="font-serif text-3xl font-bold text-brand">{{ $series->title }}</h1>
        @if ($series->description)
            <p class="mt-2 text-muted">{{ $series->description }}</p>
        @endif
    </header>
    @if ($series->parts === [])
        <p class="mt-8 text-muted">{{ __('public.no_articles') }}</p>
    @else
        <ol class="mt-6 space-y-6">
            @foreach ($series->parts as $card)
                <li class="border-b border-rule pb-4">
                    @include('partials.card', ['card' => $card, 'variant' => 'compact'])
                </li>
            @endforeach
        </ol>
    @endif
@endsection
