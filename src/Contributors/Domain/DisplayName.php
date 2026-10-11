<?php

declare(strict_types=1);

namespace Elaf\Contributors\Domain;

use Elaf\Contributors\Domain\Exception\ContributorRuleViolation;
use Stringable;

final readonly class DisplayName implements Stringable
{
    public const MAX_LENGTH = 120;

    public string $value;

    public function __construct(string $value)
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));

        if ($value === '' || mb_strlen($value) > self::MAX_LENGTH) {
            throw ContributorRuleViolation::invalidName();
        }

        $this->value = $value;
    }

    public function equals(self $other): bool
    {
        return $other->value === $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
