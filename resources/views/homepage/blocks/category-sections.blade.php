@foreach ($sections as $section)
    <section class="rounded-card border border-rule bg-paper p-4">
        <x-section-title :href="route('category.show', $section->category->slug)" :icon="$section->category->icon">{{ $section->category->name }}</x-section-title>
        <div class="grid gap-4 md:grid-cols-2">
            <x-card.medium :card="$section->articles[0]" />
            @if (count($section->articles) > 1)
                <div class="divide-y divide-rule">
                    @foreach (array_slice($section->articles, 1) as $card)
                        <x-card.list :card="$card" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endforeach
