{{-- Compact row: 120×90 thumbnail, badge, title, date. --}}
@props(['card'])
@php($url = route('article.show', ['id' => $card->id, 'slug' => $card->slug]))
<article class="flex gap-3 py-3">
    <div class="min-w-0 flex-1">
        <x-badge>{{ $card->kicker ?? $card->category->name }}</x-badge>
        <h3 class="mt-1 line-clamp-2 font-bold leading-snug"><a href="{{ $url }}" class="hover:text-accent">{{ $card->title }}</a></h3>
        <x-date :at="$card->publishedAt" class="mt-1" />
    </div>
    <a href="{{ $url }}" class="block h-[90px] w-[120px] shrink-0 overflow-hidden rounded-card bg-rule" tabindex="-1" aria-hidden="true">
        @if ($card->image)
            <x-img :path="$card->image->path" alt="" width="120" height="90" sizes="120px" />
        @else
            <span class="flex h-full items-center justify-center text-brand/40"><x-svg-icon name="newspaper" class="h-8 w-8" /></span>
        @endif
    </a>
</article>
