<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Shared\Application\Captcha\CaptchaVerifier;

/**
 * Accepts exactly the token VALID_TOKEN.
 */
final class FakeCaptchaVerifier implements CaptchaVerifier
{
    public const VALID_TOKEN = 'valid-captcha';

    public function verify(?string $token, ?string $ip): bool
    {
        return $token === self::VALID_TOKEN;
    }
}
