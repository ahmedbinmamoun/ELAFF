<?php

declare(strict_types=1);

namespace Elaf\Shared\Infrastructure\Captcha;

use Elaf\Shared\Application\Captcha\CaptchaVerifier;
use Illuminate\Http\Client\Factory;
use Throwable;

/**
 * Cloudflare Turnstile server-side check. Fails closed: no secret, no token,
 * network error or a "no" from Cloudflare all mean "not verified".
 */
final readonly class TurnstileCaptchaVerifier implements CaptchaVerifier
{
    private const ENDPOINT = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(
        private Factory $http,
        private ?string $secret,
    ) {}

    public function verify(?string $token, ?string $ip): bool
    {
        if ($this->secret === null || $this->secret === '' || $token === null || $token === '') {
            return false;
        }

        try {
            $response = $this->http->asForm()->timeout(5)->post(self::ENDPOINT, array_filter([
                'secret' => $this->secret,
                'response' => $token,
                'remoteip' => $ip,
            ]));
        } catch (Throwable) {
            return false;
        }

        return $response->successful() && $response->json('success') === true;
    }
}
