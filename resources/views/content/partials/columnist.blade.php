{{-- $item: ColumnistItem --}}
<li class="flex items-center gap-3 border-b border-rule pb-3">
    @if ($item->author->photoPath)
        <img src="{{ \Elaf\Shared\Presentation\PublicMedia::url($item->author->photoPath) }}" alt="" width="56" height="56" loading="lazy" decoding="async" class="h-14 w-14 shrink-0 rounded-full object-cover">
    @else
        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-rule font-serif text-xl text-brand" aria-hidden="true">{{ mb_substr($item->author->name, 0, 1) }}</span>
    @endif
    <div>
        <a href="{{ route('author.show', $item->author->slug) }}" class="inline-block py-1 text-sm font-bold text-brand">{{ $item->author->name }}</a>
        <a href="{{ route('article.show', ['id' => $item->latest->id, 'slug' => $item->latest->slug]) }}" class="block py-1 font-serif leading-snug hover:text-brand">{{ $item->latest->title }}</a>
        <time datetime="{{ $item->publishedAt->format(DATE_ATOM) }}" class="text-xs text-muted">{{ \Elaf\Shared\Presentation\ArabicDate::date($item->publishedAt) }}</time>
    </div>
</li>
