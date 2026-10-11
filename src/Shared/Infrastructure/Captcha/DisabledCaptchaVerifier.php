<?php

declare(strict_types=1);

namespace Elaf\Shared\Infrastructure\Captcha;

use Elaf\Shared\Application\Captcha\CaptchaVerifier;

/**
 * Local development without Turnstile keys. Never bound in production
 * (see SharedServiceProvider).
 */
final class DisabledCaptchaVerifier implements CaptchaVerifier
{
    public function verify(?string $token, ?string $ip): bool
    {
        return true;
    }
}
