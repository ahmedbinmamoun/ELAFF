@extends('layouts.app')

@section('title', $category->seoTitle ?? $category->category->name)
@section('description', $category->seoDescription ?? $category->description ?? __('site.tagline'))
@section('canonical', route('category.show', $category->category->slug).($articles->page > 1 ? '?page='.$articles->page : ''))

@push('head')
    <link rel="alternate" type="application/rss+xml" title="{{ $category->category->name }}" href="{{ route('feed', $category->category->slug) }}">
@endpush

@section('content')
    <header class="mt-6 border-b-2 border-brand pb-3">
        <h1 class="font-serif text-3xl font-bold text-brand">{{ $category->category->name }}</h1>
        @if ($category->description)
            <p class="mt-2 text-muted">{{ $category->description }}</p>
        @endif
    </header>
    @include('content.partials.listing', ['articles' => $articles])
@endsection
