<?php

declare(strict_types=1);

namespace Elaf\Shared\Application\Captcha;

/**
 * Bot check for public forms (FR-CON-01, FR-LET-01). Default adapter:
 * Cloudflare Turnstile.
 */
interface CaptchaVerifier
{
    public function verify(?string $token, ?string $ip): bool;
}
