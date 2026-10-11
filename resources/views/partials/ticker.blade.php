{{-- Breaking news (FR-ART-06): one item at a time, prev/next; without JS the first item shows. --}}
@if ($breaking !== [])
    <section class="border-b border-rule bg-paper" aria-label="{{ __('public.breaking') }}" x-data="ticker({{ count($breaking) }})">
        <div class="mx-auto flex max-w-site items-center gap-2 px-gutter">
            <span class="inline-flex shrink-0 items-center gap-1 rounded-badge bg-accent px-2 py-1 text-xs font-bold text-white"><x-svg-icon name="bolt" class="h-3.5 w-3.5" />{{ __('public.breaking') }}</span>
            <ul class="relative min-w-0 flex-1" aria-live="polite">
                @foreach ($breaking as $item)
                    <li @if (! $loop->first) x-cloak @endif x-show="index === {{ $loop->index }}">
                        <a href="{{ route('article.show', ['id' => $item->id, 'slug' => $item->slug]) }}" class="block min-h-tap truncate py-3 text-sm font-semibold leading-5 hover:text-accent">{{ $item->title }}</a>
                    </li>
                @endforeach
            </ul>
            @if (count($breaking) > 1)
                <div class="flex shrink-0">
                    <button type="button" class="flex h-tap w-tap items-center justify-center text-muted hover:text-brand" x-on:click="previous()" aria-label="{{ __('public.previous') }}"><x-svg-icon name="chevron-right" /></button>
                    <button type="button" class="flex h-tap w-tap items-center justify-center text-muted hover:text-brand" x-on:click="next()" aria-label="{{ __('public.next') }}"><x-svg-icon name="chevron-left" /></button>
                </div>
            @endif
        </div>
    </section>
@endif
