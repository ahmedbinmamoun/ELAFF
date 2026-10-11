{{-- $card: ArticleCard; $guest: bool; $linked: bool (false in compact card metadata, where links would be too small to tap) --}}
@if ($card->authors !== [])
    <span class="byline">
        @foreach ($card->authors as $author)
            @if ($linked ?? true)
                <a href="{{ route('author.show', $author->slug) }}" class="inline-block py-1 font-semibold hover:text-brand">{{ $author->name }}</a>@else<span class="font-semibold">{{ $author->name }}</span>@endif@if (! $loop->last)، @endif
        @endforeach
        @if ($guest ?? false)
            <span class="ms-1 rounded bg-rule px-1.5 py-0.5 text-xs font-normal text-muted">{{ __('public.guest_writer') }}</span>
        @endif
    </span>
@endif
