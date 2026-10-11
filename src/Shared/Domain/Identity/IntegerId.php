<?php

declare(strict_types=1);

namespace Elaf\Shared\Domain\Identity;

use Elaf\Shared\Domain\Exception\InvalidIdentifier;
use Stringable;

/**
 * Auto-increment identifier (ADR-0002). Concrete ids (ArticleId, IssueId…)
 * extend this so they cannot be mixed up with each other.
 */
abstract readonly class IntegerId implements Stringable
{
    final public function __construct(public int $value)
    {
        if ($value < 1) {
            throw InvalidIdentifier::mustBePositive(static::class, $value);
        }
    }

    public static function fromInt(int $value): static
    {
        return new static($value);
    }

    public function equals(self $other): bool
    {
        return $other::class === static::class && $other->value === $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
