<?php

declare(strict_types=1);

namespace Elaf\Engagement\Domain\Letter\Exception;

use Elaf\Shared\Domain\Exception\DomainException;

/**
 * A letter value broke a rule: $field names the form field, $reason the
 * translation key.
 */
final class InvalidLetter extends DomainException
{
    private function __construct(public readonly string $field, public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public static function because(string $field, string $reason, string $message): self
    {
        return new self($field, $reason, $message);
    }
}
