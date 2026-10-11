<?php

declare(strict_types=1);

namespace Elaf\Contributors\Domain\Exception;

use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Exception\DomainException;

final class ContributorNotFound extends DomainException
{
    public static function withId(UserId $id): self
    {
        return new self(sprintf('Contributor %d does not exist.', $id->value));
    }
}
