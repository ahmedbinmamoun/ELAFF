{{-- $issue: IssueSummary --}}
<section class="defer-render rounded-card border border-rule bg-paper p-4" aria-labelledby="current-issue-title">
    <x-section-title id="current-issue-title" icon="newspaper" :href="route('issues.index')">{{ __('public.issue.current') }}</x-section-title>
    <div class="flex gap-4 md:flex-col">
        <a href="{{ route('issues.show', $issue->number) }}" class="block w-32 shrink-0 overflow-hidden rounded-badge bg-rule shadow-sm md:w-full" style="aspect-ratio: {{ $issue->coverWidth }} / {{ $issue->coverHeight }}">
            <img src="{{ \Elaf\Publishing\Presentation\Http\IssueMedia::url($issue->coverThumbPath) }}" alt="{{ __('public.issue.page_alt', ['number' => $issue->number, 'page' => 1]) }}"
                 width="320" height="{{ (int) round(320 * $issue->coverHeight / max(1, $issue->coverWidth)) }}" loading="lazy" decoding="async" class="h-full w-full object-cover">
        </a>
        <div class="flex min-w-0 flex-col gap-2">
            <p class="font-serif text-xl font-bold text-brand">{{ __('public.issue.number', ['number' => $issue->number]) }}</p>
            <x-date :at="$issue->publishedOn" />
            @include('publishing.partials.actions', ['issue' => $issue, 'compact' => true])
        </div>
    </div>
</section>
