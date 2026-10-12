@extends('layouts.app')

@section('title', $page->seoTitle ?? $page->title)
@section('description', $page->seoDescription ?? $page->title)
@section('canonical', route('page.show', $page->slug))

@section('content')
    <article class="mx-auto max-w-3xl rounded-card border border-rule bg-paper p-4 sm:p-8">
        <h1 class="font-serif text-2xl font-bold text-brand sm:text-3xl">{{ $page->title }}</h1>
        <div class="article-body mt-5" x-ignore>
            {!! $page->bodyHtml !!}
        </div>
    </article>
@endsection
