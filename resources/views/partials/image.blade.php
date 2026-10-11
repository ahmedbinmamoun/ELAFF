{{-- $image: ImageRef; $eager: bool; $alt: string. The box reserves 16:9 so nothing shifts. --}}
<div class="aspect-video overflow-hidden bg-rule">
    <img src="{{ \Elaf\Shared\Presentation\PublicMedia::url($image->path) }}" alt="{{ $alt }}" width="800" height="450"
         class="h-full w-full object-cover" decoding="async"
         @if ($eager ?? false) fetchpriority="high" @else loading="lazy" @endif>
</div>
