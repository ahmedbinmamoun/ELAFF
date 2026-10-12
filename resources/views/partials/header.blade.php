@php
    $navLimit = 7;
    $primary = array_slice($navigation, 0, $navLimit);
    $more = array_slice($navigation, $navLimit);
    $isHome = request()->routeIs('home');
    $currentSlug = request()->route('slug');
@endphp
<header x-data="{ drawer: false, search: false }" x-on:keydown.escape.window="drawer = false; search = false" class="bg-paper">
    {{-- Desktop: date and social row --}}
    <div class="hidden border-b border-rule lg:block">
        <div class="mx-auto flex max-w-site items-center justify-between px-gutter py-2 text-xs text-muted">
            <span class="inline-flex items-center gap-1.5"><x-svg-icon name="calendar" class="h-4 w-4" />{{ $today }}</span>
            <div class="flex items-center gap-5">
                @if ($site['social']['whatsapp'] ?? null)
                    <a href="{{ $site['social']['whatsapp'] }}" rel="noopener" class="inline-flex items-center gap-2 hover:text-brand">
                        {{ __('public.whatsapp_channel') }}
                        <span class="rounded-full bg-brand/10 px-2.5 py-1 font-semibold text-brand">{{ __('public.click_here') }}</span>
                    </a>
                @endif
                @include('partials.social', ['class' => 'h-4 w-4', 'linkClass' => 'flex h-8 w-8 items-center justify-center text-brand hover:text-accent'])
            </div>
        </div>
    </div>

    {{-- Logo row --}}
    <div class="mx-auto flex max-w-site items-center gap-2 px-gutter py-2 lg:gap-5 lg:py-4">
        <button type="button" class="flex h-tap w-tap shrink-0 items-center justify-center rounded-card text-brand lg:hidden" x-on:click="drawer = true" aria-label="{{ __('public.menu') }}">
            <x-svg-icon name="menu" class="h-6 w-6" />
        </button>
        <a href="{{ route('home') }}" class="flex flex-1 flex-col items-center leading-none lg:flex-none lg:items-start" aria-label="{{ $site['name'] ?? __('site.name') }}">
            @if ($site['logo_url'] ?? null)
                {{-- Uploaded logo (FR-SET-01): a fixed height keeps the header from shifting. --}}
                <img src="{{ $site['logo_url'] }}" alt="{{ $site['name'] ?? __('site.name') }}" height="64" class="h-12 w-auto lg:h-20" fetchpriority="high">
            @else
                <span class="font-serif text-4xl font-black text-brand lg:text-6xl">{{ $site['name'] ?? __('site.name') }}</span>
                @if ($site['logo_suffix'] ?? null)
                    <span class="mt-1 text-sm font-bold text-accent lg:text-lg">{{ $site['logo_suffix'] }}</span>
                @endif
            @endif
        </a>
        <div class="hidden lg:block">
            @if ($site['motto'] ?? null)<p class="text-xl font-bold text-brand">{{ $site['motto'] }}</p>@endif
            @if ($site['description'] ?? null)<p class="text-sm text-muted">{{ $site['description'] }}</p>@endif
        </div>
        <button type="button" class="flex h-tap w-tap shrink-0 items-center justify-center rounded-card text-brand lg:hidden" x-on:click="search = !search" x-bind:aria-expanded="search.toString()" aria-expanded="false" aria-controls="mobile-search" aria-label="{{ __('public.search') }}">
            <x-svg-icon name="search" class="h-6 w-6" />
        </button>
    </div>
    @if ($site['motto'] ?? null)
        <p class="-mt-1 pb-2 text-center text-xs text-muted lg:hidden">{{ $site['motto'] }}</p>
    @endif

    {{-- Desktop: masthead --}}
    @php($masthead = array_filter($site['masthead'] ?? []))
    @if ($masthead !== [] || ($site['email'] ?? null))
        <div class="mx-auto hidden max-w-site flex-wrap items-center gap-x-8 gap-y-1 px-gutter pb-3 text-xs text-muted lg:flex">
            @foreach ($masthead as $role => $name)
                <span>{{ __('public.masthead.'.$role) }}: <strong class="font-semibold text-ink">{{ $name }}</strong></span>
            @endforeach
            @if ($site['email'] ?? null)
                <a href="mailto:{{ $site['email'] }}" class="inline-flex items-center gap-1 hover:text-brand" dir="ltr"><x-svg-icon name="mail" class="h-4 w-4" />{{ $site['email'] }}</a>
            @endif
        </div>
    @endif

    {{-- Mobile search --}}
    <form id="mobile-search" action="{{ route('search') }}" method="get" role="search" class="border-t border-rule px-gutter py-2 lg:hidden" x-show="search" x-cloak>
        <label for="q-mobile" class="sr-only">{{ __('public.search') }}</label>
        <input id="q-mobile" type="search" name="q" placeholder="{{ __('public.search_placeholder') }}" class="h-tap w-full rounded-full border border-rule bg-canvas px-4">
    </form>

    {{-- Section navigation --}}
    <nav class="bg-brand text-white" aria-label="{{ __('public.sections') }}">
        <div class="mx-auto flex max-w-site items-stretch">
            <button type="button" class="hidden h-12 w-12 shrink-0 items-center justify-center hover:bg-brand-light lg:flex" x-on:click="drawer = true" aria-label="{{ __('public.menu') }}">
                <x-svg-icon name="menu" class="h-6 w-6" />
            </button>
            <ul class="nav-scroll flex min-w-0 flex-1 overflow-x-auto whitespace-nowrap text-sm font-semibold">
                <li><a href="{{ route('home') }}" class="flex h-12 items-center px-4 {{ $isHome ? 'bg-accent' : 'hover:bg-brand-light' }}" @if ($isHome) aria-current="page" @endif>{{ __('public.home') }}</a></li>
                @foreach ($primary as $item)
                    @php($active = $currentSlug === $item->slug && request()->routeIs('category.show'))
                    <li><a href="{{ route('category.show', $item->slug) }}" class="flex h-12 items-center px-4 {{ $active ? 'bg-accent' : 'hover:bg-brand-light' }}" @if ($active) aria-current="page" @endif>{{ $item->name }}</a></li>
                @endforeach
            </ul>
            @if ($more !== [])
                <div class="relative shrink-0" x-data="{ open: false }" x-on:click.outside="open = false">
                    <button type="button" class="flex h-12 items-center gap-1 px-3 text-sm font-semibold hover:bg-brand-light" x-on:click="open = !open" x-bind:aria-expanded="open.toString()" aria-expanded="false" aria-haspopup="true">
                        {{ __('public.more') }} <x-svg-icon name="chevron-down" class="h-4 w-4" />
                    </button>
                    <ul class="absolute end-0 top-full z-30 w-52 rounded-b-card bg-paper py-1 text-ink shadow-lg" x-show="open" x-cloak x-transition.opacity>
                        @foreach ($more as $item)
                            <li><a href="{{ route('category.show', $item->slug) }}" class="flex min-h-tap items-center gap-2 px-4 hover:bg-canvas">
                                @if ($item->icon)<x-svg-icon :name="$item->icon" class="h-5 w-5 text-brand" />@endif{{ $item->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form action="{{ route('search') }}" method="get" role="search" class="hidden w-72 shrink-0 items-center px-3 lg:flex">
                <label for="q-desktop" class="sr-only">{{ __('public.search') }}</label>
                <div class="relative w-full">
                    <input id="q-desktop" type="search" name="q" placeholder="{{ __('public.search_placeholder') }}" class="h-9 w-full rounded-full bg-white/10 pe-10 ps-4 text-sm text-white placeholder:text-white/70 focus:bg-white/20">
                    <button type="submit" class="absolute inset-y-0 end-0 flex w-10 items-center justify-center" aria-label="{{ __('public.search') }}"><x-svg-icon name="search" class="h-5 w-5" /></button>
                </div>
            </form>
        </div>
    </nav>

    {{-- Drawer --}}
    <div class="fixed inset-0 z-50" x-show="drawer" x-cloak>
        <div class="absolute inset-0 bg-black/50" x-on:click="drawer = false" x-transition.opacity></div>
        <div class="absolute inset-y-0 start-0 flex w-80 max-w-[85vw] flex-col overflow-y-auto bg-paper shadow-xl" role="dialog" aria-modal="true" aria-label="{{ __('public.menu') }}" x-show="drawer" x-transition>
            <div class="flex items-center justify-between border-b border-rule px-4 py-2">
                <span class="font-serif text-2xl font-black text-brand">{{ $site['name'] ?? __('site.name') }}</span>
                <button type="button" class="flex h-tap w-tap items-center justify-center" x-on:click="drawer = false" aria-label="{{ __('public.close') }}"><x-svg-icon name="close" class="h-6 w-6" /></button>
            </div>
            <ul class="py-2">
                <li><a href="{{ route('home') }}" class="flex min-h-tap items-center gap-3 px-4 font-semibold hover:bg-canvas"><x-svg-icon name="home" class="h-5 w-5 text-brand" />{{ __('public.home') }}</a></li>
                @foreach ($navigation as $item)
                    <li><a href="{{ route('category.show', $item->slug) }}" class="flex min-h-tap items-center gap-3 px-4 hover:bg-canvas">
                        <x-svg-icon :name="$item->icon ?? 'grid'" class="h-5 w-5 text-brand" />{{ $item->name }}</a></li>
                @endforeach
            </ul>
            <ul class="border-t border-rule py-2 text-sm">
                <li><a href="{{ route('authors.index') }}" class="flex min-h-tap items-center gap-3 px-4 hover:bg-canvas"><x-svg-icon name="users" class="h-5 w-5 text-brand" />{{ __('public.columnists') }}</a></li>
                <li><a href="{{ route('issues.index') }}" class="flex min-h-tap items-center gap-3 px-4 hover:bg-canvas"><x-svg-icon name="newspaper" class="h-5 w-5 text-brand" />{{ __('public.issues') }}</a></li>
                <li><a href="{{ route('favorites') }}" class="flex min-h-tap items-center gap-3 px-4 hover:bg-canvas"><x-svg-icon name="heart" class="h-5 w-5 text-brand" />{{ __('public.favorites') }}</a></li>
                <li><a href="{{ url('/writers/register') }}" class="flex min-h-tap items-center gap-3 px-4 hover:bg-canvas"><x-svg-icon name="pen" class="h-5 w-5 text-brand" />{{ __('public.write_for_us') }}</a></li>
            </ul>
            <div class="mt-auto border-t border-rule px-4 py-3">
                @include('partials.social', ['class' => 'h-5 w-5', 'linkClass' => 'flex h-tap w-tap items-center justify-center text-brand'])
            </div>
        </div>
    </div>
</header>
