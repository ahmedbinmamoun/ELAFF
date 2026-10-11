{{-- $card: ArticleCard; $variant: lead|standard|compact; $eager: bool --}}
@php($url = route('article.show', ['id' => $card->id, 'slug' => $card->slug]))
@php($variant ??= 'standard')
<article class="card card-{{ $variant }}">
    @if ($variant !== 'compact' && $card->image)
        <a href="{{ $url }}" tabindex="-1" aria-hidden="true" class="mb-2 block">
            @include('partials.image', ['image' => $card->image, 'alt' => $card->title, 'eager' => $eager ?? false])
        </a>
    @endif
    @if ($card->kicker)
        <p class="kicker">{{ $card->kicker }}</p>
    @endif
    @if ($variant === 'lead')
        <h2 class="font-serif text-2xl font-bold leading-snug sm:text-3xl"><a href="{{ $url }}" class="hover:text-brand">{{ $card->title }}</a></h2>
        @if ($card->excerpt)
            <p class="mt-2 text-muted">{{ $card->excerpt }}</p>
        @endif
    @elseif ($variant === 'compact')
        <h3 class="font-semibold leading-snug"><a href="{{ $url }}" class="hover:text-brand">{{ $card->title }}</a></h3>
    @else
        <h3 class="font-serif text-lg font-bold leading-snug"><a href="{{ $url }}" class="hover:text-brand">{{ $card->title }}</a></h3>
    @endif
    <p class="mt-1 flex flex-wrap gap-x-2 text-xs text-muted">
        @include('partials.byline', ['card' => $card, 'guest' => false, 'linked' => false])
        <time datetime="{{ $card->publishedAt->format(DATE_ATOM) }}">{{ \Elaf\Shared\Presentation\ArabicDate::date($card->publishedAt) }}</time>
    </p>
</article>
