@extends('layouts.app')

@section('canonical', route('home'))

@section('content')
    <div class="space-y-6">
        @foreach ($regions['top'] as $block)
            {{ $block }}
        @endforeach

        <div class="grid gap-6 md:grid-cols-12">
            <aside class="hidden lg:col-span-3 lg:block" aria-label="{{ __('public.sections') }}">
                @include('homepage.partials.sections-nav')
            </aside>
            <div class="min-w-0 space-y-6 md:col-span-7 lg:col-span-6">
                @foreach ($regions['main'] as $block)
                    {{ $block }}
                @endforeach
            </div>
            <aside class="min-w-0 space-y-6 md:col-span-5 lg:col-span-3">
                @foreach ($regions['aside'] as $block)
                    {{ $block }}
                @endforeach
            </aside>
        </div>

        @foreach ($regions['bottom'] as $block)
            {{ $block }}
        @endforeach

        @if (array_merge(...array_values($regions)) === [])
            <p class="rounded-card bg-paper p-8 text-center text-muted">{{ __('public.no_articles') }}</p>
        @endif
    </div>
@endsection
