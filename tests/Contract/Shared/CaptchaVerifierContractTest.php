<?php

declare(strict_types=1);

use Elaf\Shared\Infrastructure\Captcha\TurnstileCaptchaVerifier;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\Support\Fakes\FakeCaptchaVerifier;

it('accepts only what Cloudflare confirms, and fails closed', function (): void {
    Http::fake([
        'challenges.cloudflare.com/*' => static fn ($request) => Http::response(['success' => $request['response'] === 'good']),
    ]);
    $turnstile = new TurnstileCaptchaVerifier(app(Factory::class), 'secret');

    expect($turnstile->verify('good', '1.2.3.4'))->toBeTrue()
        ->and($turnstile->verify('bad', null))->toBeFalse()
        ->and($turnstile->verify(null, null))->toBeFalse()
        ->and((new TurnstileCaptchaVerifier(app(Factory::class), null))->verify('good', null))->toBeFalse();

    Http::assertSent(static fn ($request): bool => $request['secret'] === 'secret' && ($request->data()['remoteip'] ?? null) === '1.2.3.4');
});

it('treats a Cloudflare outage as not verified', function (): void {
    Http::fake(['challenges.cloudflare.com/*' => Http::response('', 500)]);

    expect((new TurnstileCaptchaVerifier(app(Factory::class), 'secret'))->verify('good', null))->toBeFalse();
});

it('has a fake with the same contract shape', function (): void {
    $fake = new FakeCaptchaVerifier;

    expect($fake->verify(FakeCaptchaVerifier::VALID_TOKEN, null))->toBeTrue()
        ->and($fake->verify(null, null))->toBeFalse();
});
