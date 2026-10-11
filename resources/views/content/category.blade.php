@extends('layouts.app')

@section('title', $category->seoTitle ?? $category->category->name)
@section('description', $category->seoDescription ?? $category->description ?? __('site.tagline'))
@section('canonical', route('category.show', $category->category->slug).($articles->page > 1 ? '?page='.$articles->page : ''))

@push('head')
    <link rel="alternate" type="application/rss+xml" title="{{ $category->category->name }}" href="{{ route('feed', $category->category->slug) }}">
@endpush

@section('content')
    <x-page-header :title="$category->category->name" :icon="$category->category->icon">
        @if ($category->description)<p class="mt-2 text-muted">{{ $category->description }}</p>@endif
    </x-page-header>
    <x-with-aside :most-read="$mostRead">
        @include('content.partials.listing', ['articles' => $articles])
    </x-with-aside>
@endsection
