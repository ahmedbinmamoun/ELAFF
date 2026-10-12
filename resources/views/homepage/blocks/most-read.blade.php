<section class="rounded-card border border-rule bg-paper p-4" aria-labelledby="most-read-title">
    <x-section-title id="most-read-title" icon="flame">{{ __('public.most_read') }}</x-section-title>
    <ol class="divide-y divide-rule">
        @foreach ($cards as $card)
            <li class="flex gap-3 py-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-badge bg-accent text-sm font-bold text-white">{{ $loop->iteration }}</span>
                <div class="min-w-0">
                    <a href="{{ route('article.show', ['id' => $card->id, 'slug' => $card->slug]) }}" class="line-clamp-2 min-h-6 font-bold leading-6 hover:text-accent">{{ $card->title }}</a>
                    <x-date :at="$card->publishedAt" class="mt-1" />
                </div>
            </li>
        @endforeach
    </ol>
</section>
