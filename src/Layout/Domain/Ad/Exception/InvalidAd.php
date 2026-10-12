<?php

declare(strict_types=1);

namespace Elaf\Layout\Domain\Ad\Exception;

use Elaf\Shared\Domain\Exception\DomainException;

/**
 * An ad value broke a rule. $reason is a translation key (lang/ar/errors.php).
 */
final class InvalidAd extends DomainException
{
    private function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public static function because(string $reason, string $message): self
    {
        return new self($reason, $message);
    }
}
