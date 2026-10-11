<?php

declare(strict_types=1);

namespace Elaf\Shared\Application\Authorization;

use RuntimeException;

final class NotAuthorized extends RuntimeException
{
    public static function lacks(string $ability): self
    {
        return new self(sprintf('The current user lacks the "%s" permission.', $ability));
    }

    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
