<?php

declare(strict_types=1);

namespace Elaf\Engagement\Application\Letter\Submit;

use Elaf\Engagement\Application\Letter\LetterModeration;
use Elaf\Engagement\Domain\Letter\Exception\InvalidLetter;
use Elaf\Engagement\Domain\Letter\ReaderLetter;
use Elaf\Engagement\Domain\Letter\ReaderLetterId;
use Elaf\Engagement\Domain\Letter\Sender;
use Elaf\Shared\Application\Captcha\CaptchaVerifier;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Domain\Text\OptionalText;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-LET-01: a reader's letter waits in the moderation queue. The honeypot
 * and the rate limit are checked by the web layer; the captcha here.
 */
final readonly class SubmitReaderLetterHandler
{
    public function __construct(
        private LetterModeration $letters,
        private CaptchaVerifier $captcha,
        private Clock $clock,
    ) {}

    public function handle(SubmitReaderLetterCommand $command): ReaderLetterId
    {
        if (! $this->captcha->verify($command->captchaToken, $command->ip)) {
            throw InvalidInput::because('captcha', 'captcha_failed', 'The captcha check failed.');
        }

        try {
            $letter = ReaderLetter::submit(
                $this->letters->nextId(),
                new Sender(trim($command->name), trim($command->email), OptionalText::normalize($command->phone)),
                $command->title,
                $command->body,
                $command->ipHash,
                $this->clock,
            );
        } catch (InvalidLetter $e) {
            throw InvalidInput::because($e->field, $e->reason, $e->getMessage());
        }

        $this->letters->persist($letter);

        return $letter->id();
    }
}
