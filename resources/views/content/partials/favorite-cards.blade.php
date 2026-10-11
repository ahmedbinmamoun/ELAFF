@if ($cards !== [])
    <div class="divide-y divide-rule rounded-card border border-rule bg-paper px-4">
        @foreach ($cards as $card)
            <div class="flex items-center gap-2">
                <div class="min-w-0 flex-1"><x-card.list :card="$card" /></div>
                <x-favorite-button :id="$card->id" :label="false" />
            </div>
        @endforeach
    </div>
@endif
