<nav class="rounded-card border border-rule bg-paper p-4">
    <x-section-title>{{ __('public.paper_sections') }}</x-section-title>
    <ul class="divide-y divide-rule">
        @foreach ($navigation as $item)
            <li><a href="{{ route('category.show', $item->slug) }}" class="flex min-h-tap items-center gap-3 py-2 text-sm font-semibold hover:text-accent">
                <x-svg-icon :name="$item->icon ?? 'grid'" class="h-5 w-5 text-brand" />{{ $item->name }}</a></li>
        @endforeach
    </ul>
</nav>
