{{-- Main column plus the "most read" aside (desktop: 8/4; mobile: stacked). --}}
@props(['mostRead' => []])
<div class="grid gap-6 lg:grid-cols-12">
    <div class="min-w-0 lg:col-span-8">{{ $slot }}</div>
    @if ($mostRead !== [])
        <aside class="defer-render lg:col-span-4">@include('homepage.blocks.most-read', ['cards' => $mostRead])</aside>
    @endif
</div>
