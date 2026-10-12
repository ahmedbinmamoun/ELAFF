@extends('layouts.app')

@section('title', __('public.letters.title'))
@section('description', __('public.letters.intro'))
@section('canonical', route('letters.create'))

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-page-header :title="__('public.letters.title')" icon="mail">
            <p class="mt-2 text-muted">{{ __('public.letters.intro') }}</p>
        </x-page-header>

        @if (session('letter_sent'))
            <div class="mb-4 rounded-card border border-green-200 bg-green-50 p-4 text-green-900" role="status">
                <p class="font-bold">{{ __('public.letters.sent_title') }}</p>
                <p class="mt-1 text-sm">{{ __('public.letters.sent') }}</p>
            </div>
        @endif

        <form method="post" action="{{ route('letters.store') }}" class="space-y-4 rounded-card border border-rule bg-paper p-4 sm:p-6" novalidate>
            @csrf
            @php($field = 'block w-full rounded-badge border border-rule bg-paper px-3 py-2 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20')
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="mb-1 block text-sm font-semibold">{{ __('public.letters.name') }} <span class="text-accent">*</span></label>
                    <input id="name" name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name" class="{{ $field }} min-h-tap" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                    @error('name')<p id="name-error" class="mt-1 text-sm text-accent">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="mb-1 block text-sm font-semibold">{{ __('public.letters.email') }} <span class="text-accent">*</span></label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email" dir="ltr" class="{{ $field }} min-h-tap" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email')<p id="email-error" class="mt-1 text-sm text-accent">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label for="phone" class="mb-1 block text-sm font-semibold">{{ __('public.letters.phone') }}</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="30" autocomplete="tel" dir="ltr" class="{{ $field }} min-h-tap" @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>
                @error('phone')<p id="phone-error" class="mt-1 text-sm text-accent">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="title" class="mb-1 block text-sm font-semibold">{{ __('public.letters.subject') }} <span class="text-accent">*</span></label>
                <input id="title" name="title" value="{{ old('title') }}" required maxlength="150" class="{{ $field }} min-h-tap" @error('title') aria-invalid="true" aria-describedby="title-error" @enderror>
                @error('title')<p id="title-error" class="mt-1 text-sm text-accent">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="body" class="mb-1 block text-sm font-semibold">{{ __('public.letters.body') }} <span class="text-accent">*</span></label>
                <textarea id="body" name="body" rows="8" required minlength="20" maxlength="5000" class="{{ $field }}" @error('body') aria-invalid="true" aria-describedby="body-error" @enderror>{{ old('body') }}</textarea>
                @error('body')<p id="body-error" class="mt-1 text-sm text-accent">{{ $message }}</p>@enderror
            </div>
            {{-- Honeypot: hidden from people, filled by bots. --}}
            <div class="sr-only" aria-hidden="true">
                <label for="website">Website</label>
                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>
            @if ($turnstileSiteKey)
                <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-language="ar"></div>
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            @endif
            @error('captcha')<p class="text-sm text-accent">{{ $message }}</p>@enderror
            <p class="text-xs text-muted">{{ __('public.letters.privacy') }}</p>
            <button type="submit" class="flex min-h-tap items-center gap-2 rounded-badge bg-brand px-6 font-bold text-white hover:bg-brand-dark">
                <x-svg-icon name="mail" class="h-5 w-5" />{{ __('public.letters.send') }}
            </button>
        </form>
    </div>
@endsection
