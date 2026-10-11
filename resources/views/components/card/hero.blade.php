{{-- Lead story: image under a dark gradient; white text sits on ≥ 75 % black (contrast ≥ 4.5:1). --}}
@props(['card'])
@php($url = route('article.show', ['id' => $card->id, 'slug' => $card->slug]))
<article class="relative isolate overflow-hidden rounded-card bg-brand text-white shadow-sm">
    <div class="aspect-[4/3] sm:aspect-[16/9] lg:aspect-[16/10]">
        @if ($card->image)
            <x-img :path="$card->image->path" :alt="$card->title" width="1280" height="800" eager sizes="(min-width: 1024px) 66vw, 100vw" />
        @endif
    </div>
    <div class="absolute inset-0 bg-gradient-to-t from-black/90 from-25% via-black/55 via-55% to-transparent"></div>
    <div class="absolute inset-x-0 bottom-0 p-4 sm:p-6">
        <x-badge>{{ $card->kicker ?? $card->category->name }}</x-badge>
        <h2 class="mt-2 font-serif text-2xl font-bold leading-snug sm:text-3xl lg:text-4xl">
            <a href="{{ $url }}" class="after:absolute after:inset-0 hover:underline">{{ $card->title }}</a>
        </h2>
        @if ($card->excerpt)
            <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-white sm:line-clamp-3 sm:text-base">{{ $card->excerpt }}</p>
        @endif
        <x-date :at="$card->publishedAt" class="mt-3 text-white" />
    </div>
</article>
