<footer class="defer-render mt-8 bg-brand text-sm text-white">
    <div class="mx-auto flex max-w-site flex-col items-center gap-4 px-gutter py-6 lg:flex-row lg:justify-between">
        <ul class="flex flex-wrap justify-center gap-x-2">
            @foreach ($site['footer_links'] ?? [] as $link)
                <li><a href="{{ url($link['url']) }}" class="flex min-h-tap items-center px-2 hover:underline">{{ $link['label'] }}</a></li>
            @endforeach
            <li><a href="{{ route('feed') }}" class="flex min-h-tap items-center px-2 hover:underline">{{ __('public.rss') }}</a></li>
        </ul>
        <div class="text-center text-white/80 lg:text-start">
            @if ($site['footer_text'] ?? null)<p class="mb-1">{{ $site['footer_text'] }}</p>@endif
            @if (($site['phone'] ?? null) || ($site['address'] ?? null))
                <p class="mb-1">
                    @if ($site['address'] ?? null)<span>{{ $site['address'] }}</span>@endif
                    @if ($site['phone'] ?? null)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['phone']) }}" class="ms-2 hover:underline" dir="ltr">{{ $site['phone'] }}</a>@endif
                </p>
            @endif
            <p>{{ __('public.copyright', ['year' => $year, 'name' => trim(($site['name'] ?? __('site.name')).' '.($site['logo_suffix'] ?? ''))]) }}</p>
        </div>
        @include('partials.social', ['class' => 'h-5 w-5', 'linkClass' => 'flex h-tap w-tap items-center justify-center hover:text-white/70'])
    </div>
</footer>
