<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') — {{ __('site.name') }}@else{{ __('site.name') }} — {{ __('site.tagline') }}@endif</title>
    <meta name="description" content="@yield('description', __('site.tagline'))">
    @hasSection('canonical')
        <link rel="canonical" href="@yield('canonical')">
    @endif
    <meta name="theme-color" content="#0f3d4c">
    <link rel="alternate" type="application/rss+xml" title="{{ __('site.name') }}" href="{{ route('feed') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-paper text-ink font-sans antialiased">
    <a href="#main" class="skip-link">{{ __('public.skip_to_content') }}</a>
    @include('partials.header')
    <main id="main" class="mx-auto max-w-6xl px-4 pb-12">
        @yield('content')
    </main>
    @include('partials.footer')
</body>
</html>
