<?php

declare(strict_types=1);

namespace Elaf\Publishing\Domain\Issue\Exception;

use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Shared\Domain\Exception\DomainException;

final class IssueNotFound extends DomainException
{
    public static function withId(IssueId $id): self
    {
        return new self(sprintf('Issue %d does not exist.', $id->value));
    }
}
