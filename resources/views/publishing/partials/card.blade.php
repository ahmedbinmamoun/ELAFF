{{-- $issue: IssueSummary --}}
<article class="group relative flex flex-col rounded-card border border-rule bg-paper p-2 transition hover:border-brand">
    <div class="overflow-hidden rounded-badge bg-rule" style="aspect-ratio: {{ $issue->coverWidth }} / {{ $issue->coverHeight }}">
        <img src="{{ \Elaf\Publishing\Presentation\Http\IssueMedia::url($issue->coverThumbPath) }}" alt="{{ __('public.issue.page_alt', ['number' => $issue->number, 'page' => 1]) }}"
             width="320" height="{{ (int) round(320 * $issue->coverHeight / max(1, $issue->coverWidth)) }}" loading="lazy" decoding="async" class="h-full w-full object-cover">
    </div>
    <h3 class="mt-2 font-bold text-brand">
        <a href="{{ route('issues.show', $issue->number) }}" class="after:absolute after:inset-0 group-hover:text-accent">{{ __('public.issue.number', ['number' => $issue->number]) }}</a>
    </h3>
    <x-date :at="$issue->publishedOn" />
</article>
