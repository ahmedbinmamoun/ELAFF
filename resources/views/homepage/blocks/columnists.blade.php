<section>
    <x-section-title :href="route('authors.index')" icon="pen">{{ __('public.columnists') }}</x-section-title>
    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($items as $item)
            @include('content.partials.columnist', ['item' => $item])
        @endforeach
    </ul>
</section>
