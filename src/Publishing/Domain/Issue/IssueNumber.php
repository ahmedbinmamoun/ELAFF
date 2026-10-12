<?php

declare(strict_types=1);

namespace Elaf\Publishing\Domain\Issue;

use Elaf\Publishing\Domain\Issue\Exception\InvalidIssue;

/**
 * The printed issue's sequential number (FR-ISS-01). Unique; uniqueness is
 * checked by the use cases against the repository.
 */
final readonly class IssueNumber
{
    public function __construct(public int $value)
    {
        if ($value < 1) {
            throw InvalidIssue::because('number_not_positive', sprintf('Issue number must be positive, got %d.', $value));
        }
    }

    public function equals(self $other): bool
    {
        return $other->value === $this->value;
    }
}
