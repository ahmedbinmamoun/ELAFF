{{-- Cloudflare Turnstile widget; the token goes into the field's state. --}}
@if ($siteKey)
    <div
        wire:ignore
        x-data
        x-init="window.elafTurnstileCallback = (token) => $wire.set(@js($getStatePath()), token)"
    >
        <div class="cf-turnstile" data-sitekey="{{ $siteKey }}" data-language="ar" data-callback="elafTurnstileCallback"></div>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    </div>
@endif
