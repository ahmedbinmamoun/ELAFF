{{-- $item: ColumnistItem --}}
<li class="flex items-center gap-3 rounded-card border border-rule bg-paper p-4">
    @if ($item->author->photoPath)
        <img src="{{ \Elaf\Shared\Presentation\PublicMedia::url($item->author->photoPath) }}" alt="" width="56" height="56" loading="lazy" decoding="async" class="h-14 w-14 shrink-0 rounded-full object-cover">
    @else
        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-canvas font-serif text-xl text-brand" aria-hidden="true">{{ mb_substr($item->author->name, 0, 1) }}</span>
    @endif
    <div class="min-w-0">
        <a href="{{ route('author.show', $item->author->slug) }}" class="inline-block py-1 text-sm font-bold text-accent">{{ $item->author->name }}</a>
        <a href="{{ route('article.show', ['id' => $item->latest->id, 'slug' => $item->latest->slug]) }}" class="block py-1 font-serif font-bold leading-snug hover:text-accent">{{ $item->latest->title }}</a>
        <x-date :at="$item->publishedAt" />
    </div>
</li>
