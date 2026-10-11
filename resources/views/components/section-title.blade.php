@props(['href' => null, 'icon' => null])
<h2 {{ $attributes->merge(['class' => 'mb-3 flex items-center gap-2 border-s-4 border-accent ps-2 text-lg font-bold text-brand']) }}>
    @if ($icon)<x-svg-icon :name="$icon" class="h-5 w-5 text-accent" />@endif
    @if ($href)<a href="{{ $href }}" class="hover:text-accent">{{ $slot }}</a>@else{{ $slot }}@endif
</h2>
