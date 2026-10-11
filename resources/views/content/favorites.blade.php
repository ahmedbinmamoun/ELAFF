@extends('layouts.app')

@section('title', __('public.favorites_page.title'))

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <x-page-header :title="__('public.favorites_page.title')" icon="heart">
        <p class="mt-2 text-muted">{{ __('public.favorites_page.intro') }}</p>
    </x-page-header>
    <div x-data="favoritesPage('{{ route('favorites.cards') }}')" x-init="load()">
        <p x-show="state === 'loading'" class="rounded-card border border-rule bg-paper p-6 text-muted">{{ __('public.favorites_page.loading') }}</p>
        <p x-show="state === 'empty'" x-cloak class="rounded-card border border-rule bg-paper p-6 text-muted">{{ __('public.favorites_page.empty') }}</p>
        <div x-show="state === 'ready'" x-cloak x-html="html"></div>
    </div>
@endsection
