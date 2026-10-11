@props(['at', 'time' => false])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-xs text-muted']) }}>
    <x-svg-icon name="clock" class="h-3.5 w-3.5" />
    <time datetime="{{ $at->format(DATE_ATOM) }}">{{ $time ? \Elaf\Shared\Presentation\ArabicDate::dateTime($at) : \Elaf\Shared\Presentation\ArabicDate::date($at) }}</time>
</span>
