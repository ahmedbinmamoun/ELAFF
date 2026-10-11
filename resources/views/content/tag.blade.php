@extends('layouts.app')

@section('title', __('public.tag_title', ['name' => $tag->name]))
@section('canonical', route('tag.show', $tag->slug).($articles->page > 1 ? '?page='.$articles->page : ''))

@section('content')
    <x-page-header :title="__('public.tag_title', ['name' => $tag->name])" />
    <x-with-aside :most-read="$mostRead">
        @include('content.partials.listing', ['articles' => $articles])
    </x-with-aside>
@endsection
