@extends('layouts.app')

@php($summary = $issue->summary)
@php($date = \Elaf\Shared\Presentation\ArabicDate::date($summary->publishedOn))
@php($media = \Elaf\Publishing\Presentation\Http\IssueMedia::class)
@php($cover = $issue->pages[0] ?? null)
@php($byPage = $issue->contentsByPage())
@php($viewerPages = array_map(static fn ($p): array => ['n' => $p->number, 'src' => $media::url($p->imagePath), 'w' => $p->width, 'h' => $p->height], $issue->pages))

@section('title', __('public.issue.title', ['number' => $summary->number, 'date' => $date]))
@section('description', $summary->title ?? __('public.issue.description', ['number' => $summary->number, 'date' => $date, 'pages' => $summary->pageCount]))
@section('canonical', route('issues.show', $summary->number))

@if ($issue->socialImagePath)
    @section('og_image', url($media::url($issue->socialImagePath)))
@endif

@section('content')
    <nav class="mb-3 flex items-center gap-1 text-sm text-muted" aria-label="breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-brand">{{ __('public.home') }}</a>
        <x-svg-icon name="chevron-left" class="h-4 w-4" />
        <a href="{{ route('issues.index') }}" class="font-semibold text-brand">{{ __('public.issues_page.title') }}</a>
    </nav>

    <div x-data="issueViewer" data-pages="{{ json_encode($viewerPages, JSON_UNESCAPED_SLASHES) }}"
         @keydown.window="key($event)">
        <div class="grid gap-6 lg:grid-cols-12">
            <section class="lg:col-span-7" aria-labelledby="issue-title">
                <div class="rounded-card border border-rule bg-paper p-4 sm:p-5">
                    <div class="grid gap-4 sm:grid-cols-[minmax(0,15rem)_1fr]">
                        @if ($cover)
                            <a href="{{ $media::url($cover->imagePath) }}" @click.prevent="open(1)" class="mx-auto block w-48 overflow-hidden rounded-badge bg-rule shadow-sm sm:w-auto" style="aspect-ratio: {{ $cover->width }} / {{ $cover->height }}">
                                <img src="{{ $media::url($cover->thumbPath) }}" alt="{{ __('public.issue.page_alt', ['number' => $summary->number, 'page' => 1]) }}"
                                     width="320" height="{{ (int) round(320 * $cover->height / max(1, $cover->width)) }}" fetchpriority="high" decoding="async" class="h-full w-full object-cover">
                            </a>
                        @endif
                        <div class="flex flex-col gap-3">
                            <h1 id="issue-title" class="font-serif text-3xl font-bold text-brand">{{ __('public.issue.number', ['number' => $summary->number]) }}</h1>
                            @if ($summary->title)<p class="text-lg">{{ $summary->title }}</p>@endif
                            <p class="flex flex-wrap items-center gap-3 text-sm text-muted">
                                <x-date :at="$summary->publishedOn" class="!text-sm" />
                                <span>{{ __('public.issue.pages', ['count' => $summary->pageCount]) }}</span>
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <a href="#page-1" @click.prevent="open(1)" class="flex min-h-tap items-center gap-2 rounded-badge bg-brand px-4 text-sm font-bold text-white hover:bg-brand-dark">
                                    <x-svg-icon name="pages" class="h-5 w-5" />{{ __('public.issue.browse') }}
                                </a>
                                <a href="{{ route('issues.pdf', $summary->number) }}" download="elaf-{{ $summary->number }}.pdf" rel="nofollow"
                                   class="flex min-h-tap items-center gap-2 rounded-badge border border-brand px-4 text-sm font-bold text-brand hover:bg-canvas">
                                    <x-svg-icon name="download" class="h-5 w-5" />{{ __('public.issue.download') }}
                                    <span class="font-normal text-muted">({{ __('public.issue.size', ['size' => number_format($summary->pdfSize / 1048576, 1)]) }})</span>
                                </a>
                            </div>
                            <nav class="mt-auto flex flex-wrap gap-x-4 text-sm" aria-label="{{ __('public.issues_page.title') }}">
                                @if ($issue->nextNumber)
                                    <a href="{{ route('issues.show', $issue->nextNumber) }}" rel="next" class="flex min-h-tap items-center gap-1 font-semibold text-brand hover:text-accent"><x-svg-icon name="chevron-right" class="h-4 w-4" />{{ __('public.issue.next') }}</a>
                                @endif
                                @if ($issue->previousNumber)
                                    <a href="{{ route('issues.show', $issue->previousNumber) }}" rel="prev" class="flex min-h-tap items-center gap-1 font-semibold text-brand hover:text-accent">{{ __('public.issue.previous') }}<x-svg-icon name="chevron-left" class="h-4 w-4" /></a>
                                @endif
                                <a href="{{ route('issues.index') }}" class="flex min-h-tap items-center font-semibold text-muted hover:text-brand">{{ __('public.issue.all_issues') }}</a>
                            </nav>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="lg:col-span-5">
                @if ($byPage !== [])
                    <section class="rounded-card border border-rule bg-paper p-4" aria-labelledby="issue-contents">
                        <x-section-title id="issue-contents" icon="document">{{ __('public.issue.contents') }}</x-section-title>
                        <ol class="divide-y divide-rule">
                            @foreach ($byPage as $page => $entries)
                                <li class="py-2">
                                    <a href="#page-{{ $page }}" @click.prevent="open({{ $page }})" class="text-xs font-bold text-accent hover:underline">{{ __('public.issue.page', ['page' => $page]) }}</a>
                                    <ul class="mt-1 space-y-1">
                                        @foreach ($entries as $entry)
                                            <li>
                                                @if ($entry->section)<span class="text-xs text-muted">{{ $entry->section }} · </span>@endif
                                                <a href="{{ route('article.show', ['id' => $entry->articleId, 'slug' => $entry->slug]) }}" class="font-semibold leading-snug hover:text-accent">{{ $entry->title }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @else
                    @include('homepage.blocks.most-read', ['cards' => $mostRead])
                @endif
            </aside>
        </div>

        <section class="mt-6" aria-labelledby="issue-pages">
            <x-section-title id="issue-pages" icon="pages">{{ __('public.issue.all_pages') }}</x-section-title>
            <nav class="nav-scroll mb-4 flex gap-2 overflow-x-auto pb-1" aria-label="{{ __('public.issue.all_pages') }}">
                @foreach ($issue->pages as $page)
                    <a href="#page-{{ $page->number }}" class="shrink-0 rounded-badge border border-rule bg-paper p-1 text-center text-xs hover:border-brand">
                        <img src="{{ $media::url($page->thumbPath) }}" alt="" width="64" height="{{ (int) round(64 * $page->height / max(1, $page->width)) }}" loading="lazy" decoding="async" class="h-auto w-16">
                        <span class="mt-1 block">{{ $page->number }}</span>
                    </a>
                @endforeach
            </nav>
            <ol class="mx-auto max-w-3xl space-y-4">
                @foreach ($issue->pages as $page)
                    <li id="page-{{ $page->number }}" class="defer-render scroll-mt-4 rounded-card border border-rule bg-paper p-2">
                        <a href="{{ $media::url($page->imagePath) }}" @click.prevent="open({{ $page->number }})" class="block bg-rule" style="aspect-ratio: {{ $page->width }} / {{ $page->height }}">
                            <img src="{{ $media::url($page->imagePath) }}" alt="{{ __('public.issue.page_alt', ['number' => $summary->number, 'page' => $page->number]) }}"
                                 width="{{ $page->width }}" height="{{ $page->height }}" loading="lazy" decoding="async" class="h-full w-full object-contain">
                        </a>
                        <p class="py-1 text-center text-xs text-muted">{{ __('public.issue.page_of', ['page' => $page->number, 'count' => $summary->pageCount]) }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Full-screen viewer (JS only): swipe, arrows, zoom. Without JS the list above is the viewer. --}}
        <div id="issue-viewer" x-show="isOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex flex-col bg-black/95 text-white"
             role="dialog" aria-modal="true" aria-label="{{ __('public.issue.viewer') }}">
            <div class="flex items-center gap-2 px-2 pt-[env(safe-area-inset-top)]">
                <button type="button" x-ref="close" @click="close()" class="flex h-tap w-tap items-center justify-center rounded-badge hover:bg-white/10" aria-label="{{ __('public.close') }}">
                    <x-svg-icon name="close" class="h-6 w-6" />
                </button>
                <p class="flex-1 text-center text-sm" aria-live="polite" x-text="'{{ __('public.issue.page_of', ['page' => '__P__', 'count' => $summary->pageCount]) }}'.replace('__P__', index + 1)"></p>
                <button type="button" @click="toggleZoom()" class="flex h-tap w-tap items-center justify-center rounded-badge hover:bg-white/10" :aria-label="zoomed ? '{{ __('public.issue.zoom_out') }}' : '{{ __('public.issue.zoom_in') }}'">
                    <span x-show="!zoomed"><x-svg-icon name="zoom-in" class="h-6 w-6" /></span>
                    <span x-show="zoomed"><x-svg-icon name="zoom-out" class="h-6 w-6" /></span>
                </button>
                <a href="{{ route('issues.pdf', $summary->number) }}" download="elaf-{{ $summary->number }}.pdf" rel="nofollow" class="flex h-tap w-tap items-center justify-center rounded-badge hover:bg-white/10" aria-label="{{ __('public.issue.download') }}">
                    <x-svg-icon name="download" class="h-6 w-6" />
                </a>
            </div>
            <div class="relative min-h-0 flex-1">
                <div x-ref="stage" class="h-full overflow-auto [touch-action:pan-x_pan-y_pinch-zoom]" @touchstart.passive="touchStart($event)" @touchend="touchEnd($event)">
                    <img :src="current.src" :width="current.w" :height="current.h" :alt="'{{ __('public.issue.page_alt', ['number' => $summary->number, 'page' => '__P__']) }}'.replace('__P__', index + 1)"
                         @dblclick="toggleZoom()" class="mx-auto" :class="zoomed ? 'w-[220%] max-w-none' : 'h-full w-auto max-w-full object-contain'">
                </div>
                <button type="button" @click="previous()" x-show="index > 0" class="absolute inset-y-0 start-0 hidden w-16 items-center justify-center hover:bg-white/5 md:flex" aria-label="{{ __('public.issue.previous_page') }}">
                    <x-svg-icon name="chevron-right" class="h-8 w-8" />
                </button>
                <button type="button" @click="next()" x-show="index < pages.length - 1" class="absolute inset-y-0 end-0 hidden w-16 items-center justify-center hover:bg-white/5 md:flex" aria-label="{{ __('public.issue.next_page') }}">
                    <x-svg-icon name="chevron-left" class="h-8 w-8" />
                </button>
            </div>
            <div class="flex justify-between gap-2 px-2 pb-[calc(0.5rem+env(safe-area-inset-bottom))] pt-2 md:hidden">
                <button type="button" @click="previous()" :disabled="index === 0" class="flex min-h-tap flex-1 items-center justify-center gap-1 rounded-badge bg-white/10 text-sm disabled:opacity-40">
                    <x-svg-icon name="chevron-right" class="h-5 w-5" />{{ __('public.issue.previous_page') }}
                </button>
                <button type="button" @click="next()" :disabled="index === pages.length - 1" class="flex min-h-tap flex-1 items-center justify-center gap-1 rounded-badge bg-white/10 text-sm disabled:opacity-40">
                    {{ __('public.issue.next_page') }}<x-svg-icon name="chevron-left" class="h-5 w-5" />
                </button>
            </div>
        </div>
    </div>
@endsection
