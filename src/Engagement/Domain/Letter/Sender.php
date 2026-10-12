<?php

declare(strict_types=1);

namespace Elaf\Engagement\Domain\Letter;

use Elaf\Engagement\Domain\Letter\Exception\InvalidLetter;

/**
 * Who wrote the letter. Email and phone are private (FR-LET-03): only the
 * name may ever be published.
 */
final readonly class Sender
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone = null,
    ) {
        if (trim($name) === '' || mb_strlen($name) > 100) {
            throw InvalidLetter::because('name', 'letter_name', 'A name of at most 100 characters is required.');
        }

        if (mb_strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidLetter::because('email', 'invalid_email', 'The email address is not valid.');
        }

        if ($phone !== null && preg_match('/^\+?[0-9 ()\-]{6,30}$/', $phone) !== 1) {
            throw InvalidLetter::because('phone', 'invalid_phone', 'The phone number is not valid.');
        }
    }
}
