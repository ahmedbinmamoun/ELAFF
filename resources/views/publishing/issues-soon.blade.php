@extends('layouts.app')

@section('title', __('public.issues_page.title'))

@section('content')
    <x-page-header :title="__('public.issues_page.title')" icon="newspaper">
        <p class="mt-2 text-muted">{{ __('public.issues_page.soon') }}</p>
    </x-page-header>
@endsection
