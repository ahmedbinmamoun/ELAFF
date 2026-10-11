<section class="space-y-4" x-data="{ scroll(direction) { $refs.strip.scrollBy({ left: direction * $refs.strip.clientWidth * 0.8, behavior: 'smooth' }) } }">
    @if ($featured !== [])
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($featured as $card)
                <x-card.medium :card="$card" />
            @endforeach
        </div>
    @endif
    @if ($strip !== [])
        <div>
            <div class="flex items-center justify-between">
                <x-section-title class="mb-0">{{ __('public.latest') }}</x-section-title>
                <div class="hidden md:flex">
                    <button type="button" class="flex h-tap w-tap items-center justify-center rounded-card text-muted hover:text-brand" x-on:click="scroll(1)" aria-label="{{ __('public.previous') }}"><x-svg-icon name="chevron-right" /></button>
                    <button type="button" class="flex h-tap w-tap items-center justify-center rounded-card text-muted hover:text-brand" x-on:click="scroll(-1)" aria-label="{{ __('public.next') }}"><x-svg-icon name="chevron-left" /></button>
                </div>
            </div>
            <div x-ref="strip" class="nav-scroll -mx-gutter mt-3 flex snap-x gap-3 overflow-x-auto px-gutter pb-1 md:mx-0 md:px-0">
                @foreach ($strip as $card)
                    <x-card.mini :card="$card" />
                @endforeach
            </div>
        </div>
    @endif
</section>
