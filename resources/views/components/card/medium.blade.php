@props(['card', 'eager' => false])
@php($url = route('article.show', ['id' => $card->id, 'slug' => $card->slug]))
<article class="flex flex-col overflow-hidden rounded-card border border-rule bg-paper shadow-sm">
    <a href="{{ $url }}" class="relative block aspect-[16/9] bg-rule" tabindex="-1" aria-hidden="true">
        @if ($card->image)
            <x-img :path="$card->image->path" :alt="$card->title" width="640" height="360" :eager="$eager" sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw" />
        @endif
        <x-badge class="absolute bottom-2 start-2">{{ $card->kicker ?? $card->category->name }}</x-badge>
    </a>
    <div class="flex flex-1 flex-col p-4">
        <h3 class="font-serif text-lg font-bold leading-snug"><a href="{{ $url }}" class="hover:text-accent">{{ $card->title }}</a></h3>
        @if ($card->excerpt)
            <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-muted">{{ $card->excerpt }}</p>
        @endif
        <div class="mt-auto flex items-center justify-between pt-3">
            <x-date :at="$card->publishedAt" />
            <x-favorite-button :id="$card->id" :label="false" />
        </div>
    </div>
</article>
