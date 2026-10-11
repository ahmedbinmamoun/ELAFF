<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@hasSection('title')@yield('title') — {{ __('site.name') }}@else{{ __('site.name') }} — {{ $site['motto'] ?? __('site.tagline') }}@endif</title>
    <meta name="description" content="@yield('description', $site['description'] ?? __('site.tagline'))">
    @hasSection('canonical')
        <link rel="canonical" href="@yield('canonical')">
    @endif
    <meta name="theme-color" content="#0e2a47">
    <link rel="alternate" type="application/rss+xml" title="{{ __('site.name') }}" href="{{ route('feed') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-canvas pb-[calc(4rem+env(safe-area-inset-bottom))] font-sans text-ink antialiased md:pb-0">
    <a href="#main" class="skip-link">{{ __('public.skip_to_content') }}</a>
    @include('partials.header')
    @include('partials.ticker')
    <main id="main" class="mx-auto max-w-site px-gutter py-4 lg:py-6">
        @yield('content')
    </main>
    @include('partials.footer')
    @include('partials.tabbar')
</body>
</html>
