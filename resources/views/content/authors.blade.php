@extends('layouts.app')

@section('title', __('public.columnists'))
@section('canonical', route('authors.index'))

@section('content')
    <header class="mt-6 border-b-2 border-brand pb-3">
        <h1 class="font-serif text-3xl font-bold text-brand">{{ __('public.columnists') }}</h1>
    </header>
    @if ($columnists === [])
        <p class="mt-8 text-muted">{{ __('public.no_articles') }}</p>
    @else
        <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($columnists as $item)
                @include('content.partials.columnist', ['item' => $item])
            @endforeach
        </ul>
    @endif
@endsection
