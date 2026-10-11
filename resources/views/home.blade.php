@extends('layouts.app')

@section('content')
    <main class="mx-auto max-w-3xl px-4 py-16 text-center">
        <h1 class="font-serif text-5xl font-bold text-brand">{{ __('site.name') }}</h1>
        <p class="mt-3 text-muted">{{ __('site.tagline') }}</p>
        <p class="mt-8 border-t border-rule pt-6">{{ __('site.coming_soon') }}</p>
    </main>
@endsection
