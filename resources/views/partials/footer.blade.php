<footer class="mt-8 bg-brand text-sm text-white">
    <div class="mx-auto grid max-w-6xl gap-6 px-4 py-8 sm:grid-cols-3">
        <div>
            <p class="font-serif text-3xl font-bold">{{ __('site.name') }}</p>
            <p class="mt-2 text-white/80">{{ __('site.tagline') }}</p>
        </div>
        <nav aria-label="{{ __('public.sections') }}">
            <ul class="grid grid-cols-2 gap-1">
                @foreach ($navigation as $item)
                    <li><a href="{{ route('category.show', $item->slug) }}" class="hover:underline">{{ $item->name }}</a></li>
                @endforeach
            </ul>
        </nav>
        <ul class="space-y-1">
            <li><a href="{{ route('authors.index') }}" class="hover:underline">{{ __('public.columnists') }}</a></li>
            <li><a href="{{ url('/writers/register') }}" class="hover:underline">{{ __('public.write_for_us') }}</a></li>
            <li><a href="{{ route('feed') }}" class="hover:underline">{{ __('public.rss') }}</a></li>
        </ul>
    </div>
    <p class="border-t border-white/20 py-3 text-center text-xs text-white/70">© {{ $year }} {{ __('site.name') }}</p>
</footer>
