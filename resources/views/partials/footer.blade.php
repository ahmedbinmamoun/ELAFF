<footer class="defer-render mt-8 bg-brand text-sm text-white">
    <div class="mx-auto flex max-w-site flex-col items-center gap-4 px-gutter py-6 lg:flex-row lg:justify-between">
        <ul class="flex flex-wrap justify-center gap-x-2">
            @foreach ($site['footer_links'] ?? [] as $link)
                <li><a href="{{ url($link['url']) }}" class="flex min-h-tap items-center px-2 hover:underline">{{ $link['label'] }}</a></li>
            @endforeach
            <li><a href="{{ route('feed') }}" class="flex min-h-tap items-center px-2 hover:underline">{{ __('public.rss') }}</a></li>
        </ul>
        <p class="text-white/80">{{ __('public.copyright', ['year' => $year, 'name' => trim(__('site.name').' '.($site['logo_suffix'] ?? ''))]) }}</p>
        @include('partials.social', ['class' => 'h-5 w-5', 'linkClass' => 'flex h-tap w-tap items-center justify-center hover:text-white/70'])
    </div>
</footer>
