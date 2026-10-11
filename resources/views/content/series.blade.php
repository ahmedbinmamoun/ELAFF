@extends('layouts.app')

@section('title', __('public.series_title', ['name' => $series->title]))
@section('description', $series->description ?? __('site.tagline'))
@section('canonical', route('series.show', $series->slug))

@section('content')
    <x-page-header :title="$series->title" icon="book">
        @if ($series->description)<p class="mt-2 text-muted">{{ $series->description }}</p>@endif
    </x-page-header>
    @if ($series->parts === [])
        <p class="rounded-card border border-rule bg-paper p-6 text-muted">{{ __('public.no_articles') }}</p>
    @else
        <ol class="divide-y divide-rule rounded-card border border-rule bg-paper px-4">
            @foreach ($series->parts as $card)
                <li><x-card.list :card="$card" /></li>
            @endforeach
        </ol>
    @endif
@endsection
