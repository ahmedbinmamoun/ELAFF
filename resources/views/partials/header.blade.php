<header class="border-b-4 border-brand">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 pt-3 text-xs text-muted">
        <span>{{ $today }}</span>
        <a href="{{ url('/writers/register') }}" class="hover:text-brand">{{ __('public.write_for_us') }}</a>
    </div>
    <div class="mx-auto max-w-6xl px-4 py-4 text-center">
        <a href="{{ route('home') }}" class="inline-block font-serif text-5xl font-bold leading-none text-brand sm:text-6xl">{{ __('site.name') }}</a>
        <p class="mt-1 text-sm text-muted">{{ __('site.tagline') }}</p>
    </div>
    <nav aria-label="{{ __('public.sections') }}" class="border-t border-rule">
        <ul class="nav-scroll mx-auto flex max-w-6xl gap-5 overflow-x-auto whitespace-nowrap px-4 py-2 text-sm font-semibold">
            <li><a href="{{ route('home') }}" class="block py-1 hover:text-brand">{{ __('public.home') }}</a></li>
            @foreach ($navigation as $item)
                <li><a href="{{ route('category.show', $item->slug) }}" class="block py-1 hover:text-brand" @if (request()->is('category/'.$item->slug)) aria-current="page" @endif>{{ $item->name }}</a></li>
            @endforeach
        </ul>
    </nav>
</header>
