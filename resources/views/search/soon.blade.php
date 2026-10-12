@extends('layouts.app')

@section('title', __('public.search_page.title'))

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <x-page-header :title="__('public.search_page.title')" icon="search">
        @if (is_string(request()->query('q')) && trim(request()->query('q')) !== '')
            <p class="mt-2 font-semibold">{{ __('public.search_page.query', ['q' => request()->query('q')]) }}</p>
        @endif
        <p class="mt-2 text-muted">{{ __('public.search_page.soon') }}</p>
    </x-page-header>
    <nav class="rounded-card border border-rule bg-paper p-4" aria-label="{{ __('public.sections') }}">
        <ul class="grid gap-1 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($menus['sections'] ?? [] as $item)
                <li><a href="{{ $item->url }}" class="flex min-h-tap items-center gap-3 rounded-card px-2 hover:bg-canvas"><x-svg-icon :name="$item->icon ?? 'grid'" class="h-5 w-5 text-brand" />{{ $item->label }}</a></li>
            @endforeach
        </ul>
    </nav>
@endsection
