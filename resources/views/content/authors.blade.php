@extends('layouts.app')

@section('title', __('public.columnists'))
@section('canonical', route('authors.index'))

@section('content')
    <x-page-header :title="__('public.columnists')" icon="pen" />
    @if ($columnists === [])
        <p class="rounded-card border border-rule bg-paper p-6 text-muted">{{ __('public.no_articles') }}</p>
    @else
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($columnists as $item)
                @include('content.partials.columnist', ['item' => $item])
            @endforeach
        </ul>
    @endif
@endsection
