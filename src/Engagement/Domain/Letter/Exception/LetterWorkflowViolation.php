<?php

declare(strict_types=1);

namespace Elaf\Engagement\Domain\Letter\Exception;

use Elaf\Shared\Domain\Exception\DomainException;

/**
 * A moderation step that the letter's state does not allow; $reason is a
 * translation key.
 */
final class LetterWorkflowViolation extends DomainException
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
