@extends('layouts.app')

@section('title', $author->author->name)
@section('description', $author->bio ?? __('public.articles_by', ['name' => $author->author->name]))
@section('canonical', route('author.show', $author->author->slug).($articles->page > 1 ? '?page='.$articles->page : ''))

@section('content')
    <header class="mb-4 flex items-center gap-4 rounded-card border border-rule bg-paper p-4 sm:p-5">
        @if ($author->author->photoPath)
            <img src="{{ \Elaf\Shared\Presentation\PublicMedia::url($author->author->photoPath) }}" alt="{{ $author->author->name }}" width="80" height="80" class="h-20 w-20 shrink-0 rounded-full object-cover">
        @else
            <span class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-canvas font-serif text-3xl text-brand" aria-hidden="true">{{ mb_substr($author->author->name, 0, 1) }}</span>
        @endif
        <div class="min-w-0">
            <h1 class="font-serif text-2xl font-bold text-brand sm:text-3xl">{{ $author->author->name }}</h1>
            @if ($author->author->title)<p class="text-muted">{{ $author->author->title }}</p>@endif
            @if ($author->bio)<p class="mt-2 max-w-2xl">{{ $author->bio }}</p>@endif
            @if ($author->socialLinks !== [])
                <ul class="mt-1 flex flex-wrap gap-1 text-sm">
                    @foreach ($author->socialLinks as $network => $link)
                        <li><a href="{{ $link }}" rel="me noopener" class="flex min-h-tap items-center px-2 text-brand underline">{{ $network }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </header>
    <x-with-aside :most-read="$mostRead">
        <x-section-title>{{ __('public.articles_by', ['name' => $author->author->name]) }}</x-section-title>
        @include('content.partials.listing', ['articles' => $articles])
    </x-with-aside>
@endsection
