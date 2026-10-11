{{-- Uploaded image with fixed box (no layout shift). Eager + high priority only for the hero. --}}
@props(['path', 'alt' => '', 'width', 'height', 'sizes' => '100vw', 'eager' => false])
@php($sources = \Elaf\Shared\Presentation\ResponsiveImage::sources($path))
<img src="{{ $sources['src'] }}"
     @if ($sources['srcset']) srcset="{{ $sources['srcset'] }}" sizes="{{ $sizes }}" @endif
     alt="{{ $alt }}" width="{{ $width }}" height="{{ $height }}" decoding="async"
     @if ($eager) fetchpriority="high" @else loading="lazy" @endif
     {{ $attributes->merge(['class' => 'h-full w-full object-cover']) }}>
