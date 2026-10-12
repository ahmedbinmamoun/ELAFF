{{-- Main column plus the "most read" aside (desktop: 8/4; mobile: stacked), with an optional ad slot on top of the aside. --}}
@props(['mostRead' => [], 'ad' => null])
<div class="grid gap-6 lg:grid-cols-12">
    <div class="min-w-0 lg:col-span-8">{{ $slot }}</div>
    @if ($mostRead !== [] || $ad)
        <aside class="defer-render min-w-0 space-y-6 lg:col-span-4">
            @if ($ad)<x-ad-slot :place="$ad" />@endif
            @if ($mostRead !== [])@include('homepage.blocks.most-read', ['cards' => $mostRead])@endif
        </aside>
    @endif
</div>
