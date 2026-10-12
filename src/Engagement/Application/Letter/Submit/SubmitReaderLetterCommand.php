<?php

declare(strict_types=1);

namespace Elaf\Engagement\Application\Letter\Submit;

/**
 * The public form. $ipHash is computed by the web layer (keyed hash, the IP
 * itself is never stored); $ip and $captchaToken only reach the captcha.
 */
final readonly class SubmitReaderLetterCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public string $title,
        public string $body,
        public string $ipHash,
        public ?string $captchaToken = null,
        public ?string $ip = null,
    ) {}
}
