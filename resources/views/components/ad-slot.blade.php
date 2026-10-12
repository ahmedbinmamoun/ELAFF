{{-- An ad slot (FR-ADS-01): renders nothing when no ad runs; otherwise reserves the slot's shape (no layout shift). --}}
@props(['place', 'eager' => false])
@php
    $slotEnum = \Elaf\Layout\Domain\Ad\AdSlot::from($place);
    $ad = app(\Elaf\Layout\Presentation\Ads\AdSlots::class)->pick($slotEnum);
@endphp
@if ($ad)
    <aside {{ $attributes->merge(['class' => 'mx-auto w-full']) }} style="max-width: {{ $slotEnum->width() }}px" aria-label="{{ __('public.ad') }}" data-ad-slot="{{ $place }}">
        <p class="mb-1 text-center text-[11px] leading-4 text-muted">{{ __('public.ad') }}</p>
        @if ($ad->imagePath)
            <a href="{{ route('ads.go', $ad->id) }}" rel="sponsored noopener" target="_blank" class="block overflow-hidden rounded-badge bg-canvas" style="aspect-ratio: {{ $slotEnum->width() }} / {{ $slotEnum->height() }}">
                <img src="{{ \Elaf\Shared\Presentation\PublicMedia::url($ad->imagePath) }}" alt="{{ $ad->title }}" width="{{ $slotEnum->width() }}" height="{{ $slotEnum->height() }}"
                     @if ($eager) fetchpriority="low" @else loading="lazy" @endif decoding="async" class="h-full w-full object-contain">
            </a>
        @else
            <div class="overflow-hidden rounded-badge" style="min-height: {{ $slotEnum->height() }}px" x-ignore>
                @if ($ad->targetUrl)
                    <a href="{{ route('ads.go', $ad->id) }}" rel="sponsored noopener" target="_blank" class="block">{!! $ad->html !!}</a>
                @else
                    {!! $ad->html !!}
                @endif
            </div>
        @endif
    </aside>
@endif
