@props(['tone' => 'accent'])
<span {{ $attributes->merge(['class' => 'inline-block rounded-badge px-2 py-0.5 text-xs font-bold leading-5 '.($tone === 'accent' ? 'bg-accent text-white' : 'bg-brand text-white')]) }}>{{ $slot }}</span>
