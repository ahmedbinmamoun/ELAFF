@extends('layouts.app')

@section('title', __('public.tag_title', ['name' => $tag->name]))
@section('canonical', route('tag.show', $tag->slug).($articles->page > 1 ? '?page='.$articles->page : ''))

@section('content')
    <header class="mt-6 border-b-2 border-brand pb-3">
        <h1 class="font-serif text-3xl font-bold text-brand">{{ __('public.tag_title', ['name' => $tag->name]) }}</h1>
    </header>
    @include('content.partials.listing', ['articles' => $articles])
@endsection
