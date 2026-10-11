@props(['card'])
@php($url = route('article.show', ['id' => $card->id, 'slug' => $card->slug]))
<article class="w-44 shrink-0 snap-start overflow-hidden rounded-card border border-rule bg-paper sm:w-48">
    <a href="{{ $url }}" class="relative block aspect-[16/10] bg-rule" tabindex="-1" aria-hidden="true">
        @if ($card->image)
            <x-img :path="$card->image->path" alt="" width="320" height="200" sizes="12rem" />
        @endif
        <x-badge class="absolute bottom-1.5 start-1.5">{{ $card->kicker ?? $card->category->name }}</x-badge>
    </a>
    <div class="p-3">
        <h3 class="line-clamp-2 text-sm font-bold leading-snug"><a href="{{ $url }}" class="hover:text-accent">{{ $card->title }}</a></h3>
        <x-date :at="$card->publishedAt" class="mt-2" />
    </div>
</article>
