{{-- $issue: IssueSummary; $compact: stacked full-width buttons for narrow boxes --}}
@php($compact = $compact ?? false)
<div class="flex {{ $compact ? 'flex-col' : 'flex-wrap' }} gap-2">
    <a href="{{ route('issues.show', $issue->number) }}" class="flex min-h-tap items-center justify-center gap-2 rounded-badge bg-brand px-4 text-sm font-bold text-white hover:bg-brand-dark">
        <x-svg-icon name="pages" class="h-5 w-5 shrink-0" />{{ __('public.issue.browse') }}
    </a>
    <a href="{{ route('issues.pdf', $issue->number) }}" download="elaf-{{ $issue->number }}.pdf" rel="nofollow"
       class="flex min-h-tap flex-wrap items-center justify-center gap-x-2 rounded-badge border border-brand px-4 text-sm font-bold text-brand hover:bg-canvas">
        <x-svg-icon name="download" class="h-5 w-5 shrink-0" />{{ __('public.issue.download') }}
        @unless ($compact)
            <span class="font-normal text-muted">({{ __('public.issue.size', ['size' => number_format($issue->pdfSize / 1048576, 1)]) }})</span>
        @endunless
    </a>
</div>
