{{-- Client-side favourites (localStorage via Alpine store); no account needed. --}}
@props(['id', 'label' => true])
<button type="button" x-data
        x-on:click="$store.favorites.toggle({{ (int) $id }})"
        x-bind:aria-pressed="$store.favorites.has({{ (int) $id }}).toString()"
        aria-pressed="false"
        {{ $attributes->merge(['class' => 'inline-flex min-h-11 min-w-11 items-center justify-center gap-1 rounded-card text-sm font-semibold text-muted hover:text-accent aria-pressed:text-accent']) }}>
    <x-svg-icon name="heart" class="h-5 w-5" />
    @if ($label)
        <span x-text="$store.favorites.has({{ (int) $id }}) ? '{{ __('public.saved') }}' : '{{ __('public.save') }}'">{{ __('public.save') }}</span>
    @else
        <span class="sr-only">{{ __('public.save') }}</span>
    @endif
</button>
