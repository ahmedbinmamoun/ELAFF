<?php

declare(strict_types=1);

namespace Elaf\Engagement\Presentation\Http;

use Elaf\Engagement\Application\Letter\Submit\SubmitReaderLetterCommand;
use Elaf\Engagement\Application\Letter\Submit\SubmitReaderLetterHandler;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * FR-LET-01: "بريد القراء" form. Spam guards: a honeypot field (bots that
 * fill it get the thank-you page and nothing is stored), a rate limit on the
 * route, and the captcha in the use case.
 */
final readonly class LetterController
{
    public const HONEYPOT = 'website';

    public function __construct(
        private SubmitReaderLetterHandler $submit,
        private Repository $config,
    ) {}

    public function create(): View
    {
        $siteKey = $this->config->get('elaf.turnstile.site_key');

        return view('engagement.letters', ['turnstileSiteKey' => is_string($siteKey) && $siteKey !== '' ? $siteKey : null]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (trim($this->text($request, self::HONEYPOT)) !== '') {
            return redirect()->route('letters.create')->with('letter_sent', true);
        }

        try {
            $this->submit->handle(new SubmitReaderLetterCommand(
                name: $this->text($request, 'name'),
                email: $this->text($request, 'email'),
                phone: $this->text($request, 'phone'),
                title: $this->text($request, 'title'),
                body: $this->text($request, 'body'),
                ipHash: hash_hmac('sha256', (string) $request->ip(), $this->key()),
                captchaToken: $this->text($request, 'cf-turnstile-response'),
                ip: $request->ip(),
            ));
        } catch (InvalidInput $e) {
            throw ValidationException::withMessages([$e->field => UseCaseErrors::message($e)]);
        }

        return redirect()->route('letters.create')->with('letter_sent', true);
    }

    /**
     * The app key makes the IP hash useless outside this installation.
     */
    private function key(): string
    {
        $key = $this->config->get('app.key');

        return is_string($key) ? $key : '';
    }

    private function text(Request $request, string $key): string
    {
        $value = $request->input($key);

        return is_string($value) ? mb_substr($value, 0, 10_000) : '';
    }
}
