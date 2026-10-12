<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @php($siteName = $site['name'] ?? __('site.name'))
    <title>@hasSection('title')@yield('title') — {{ $siteName }}@else{{ $siteName }} — {{ $site['motto'] ?? __('site.tagline') }}@endif</title>
    <meta name="description" content="@yield('description', $site['description'] ?? __('site.tagline'))">
    @hasSection('canonical')
        <link rel="canonical" href="@yield('canonical')">
    @endif
    <meta name="theme-color" content="#0e2a47">
    @if ($site['favicon_url'] ?? null)
        <link rel="icon" href="{{ $site['favicon_url'] }}">
    @endif
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @elseif ($site['og_image_url'] ?? null)
        <meta property="og:image" content="{{ url($site['og_image_url']) }}">
    @endif
    <link rel="alternate" type="application/rss+xml" title="{{ $siteName }}" href="{{ route('feed') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    @if ($site['analytics_id'] ?? null)
        {{-- Google tag (FR-SET-01), loaded after the page; CSP in Phase 7. --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $site['analytics_id'] }}"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config',@json($site['analytics_id']));</script>
    @endif
</head>
<body class="bg-canvas pb-[calc(4rem+env(safe-area-inset-bottom))] font-sans text-ink antialiased md:pb-0">
    <a href="#main" class="skip-link">{{ __('public.skip_to_content') }}</a>
    @include('partials.header')
    <x-ad-slot place="header_banner" class="px-gutter pt-3" :eager="true" />
    {{-- On the homepage the ticker is a block (FR-HOME-02 breaking_ticker). --}}
    @unless (request()->routeIs('home'))
        @include('partials.ticker')
    @endunless
    <main id="main" class="mx-auto max-w-site px-gutter py-4 lg:py-6">
        @yield('content')
    </main>
    <x-ad-slot place="footer" class="px-gutter pb-4" />
    @include('partials.footer')
    @include('partials.tabbar')
</body>
</html>
