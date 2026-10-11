@extends('layouts.app')

@section('title', $author->author->name)
@section('description', $author->bio ?? __('public.articles_by', ['name' => $author->author->name]))
@section('canonical', route('author.show', $author->author->slug).($articles->page > 1 ? '?page='.$articles->page : ''))

@section('content')
    <header class="mt-6 flex items-center gap-4 border-b-2 border-brand pb-4">
        @if ($author->author->photoPath)
            <img src="{{ \Elaf\Shared\Presentation\PublicMedia::url($author->author->photoPath) }}" alt="{{ $author->author->name }}" width="96" height="96" class="h-24 w-24 rounded-full object-cover">
        @endif
        <div>
            <h1 class="font-serif text-3xl font-bold text-brand">{{ $author->author->name }}</h1>
            @if ($author->author->title)
                <p class="text-muted">{{ $author->author->title }}</p>
            @endif
            @if ($author->bio)
                <p class="mt-2 max-w-2xl">{{ $author->bio }}</p>
            @endif
            @if ($author->socialLinks !== [])
                <ul class="mt-2 flex gap-3 text-sm">
                    @foreach ($author->socialLinks as $network => $link)
                        <li><a href="{{ $link }}" rel="me noopener" class="text-brand underline">{{ $network }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </header>
    <h2 class="mt-6 font-bold text-muted">{{ __('public.articles_by', ['name' => $author->author->name]) }}</h2>
    @include('content.partials.listing', ['articles' => $articles])
@endsection
