@props(['title', 'icon' => null])
<header {{ $attributes->merge(['class' => 'mb-4 rounded-card border border-rule bg-paper p-4 sm:p-5']) }}>
    <h1 class="flex items-center gap-2 font-serif text-2xl font-bold text-brand sm:text-3xl">
        @if ($icon)<x-svg-icon :name="$icon" class="h-7 w-7 text-accent" />@endif{{ $title }}
    </h1>
    {{ $slot }}
</header>
