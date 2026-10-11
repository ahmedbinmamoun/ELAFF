<section class="grid gap-4 lg:grid-cols-3" aria-label="{{ __('public.top_stories') }}">
    <div class="lg:order-2 lg:col-span-2">
        <x-card.hero :card="$lead" />
    </div>
    @if ($secondary !== [])
        <div class="divide-y divide-rule rounded-card border border-rule bg-paper px-4 lg:order-1">
            @foreach ($secondary as $card)
                <x-card.list :card="$card" />
            @endforeach
        </div>
    @endif
</section>
