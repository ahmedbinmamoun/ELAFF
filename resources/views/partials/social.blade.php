{{-- Configured social links only (config/elaf.php site.social). --}}
@php($social = array_filter($site['social'] ?? []))
@if ($social !== [])
    <ul class="flex items-center gap-1">
        @foreach ($social as $network => $url)
            <li><a href="{{ $url }}" rel="noopener" class="{{ $linkClass }}" aria-label="{{ __('public.social.'.$network) }}"><x-svg-icon :name="$network" :class="$class" /></a></li>
        @endforeach
    </ul>
@endif
