<?php

declare(strict_types=1);

namespace Elaf\Publishing\Domain\Issue\Exception;

use Elaf\Shared\Domain\Exception\DomainException;

/**
 * An issue value broke a rule. $reason is a translation key (lang/ar/errors.php).
 */
final class InvalidIssue extends DomainException
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
