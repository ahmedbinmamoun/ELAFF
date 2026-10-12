<?php

declare(strict_types=1);

namespace Elaf\Layout\Domain\Ad\Exception;

use Elaf\Layout\Domain\Ad\AdId;
use Elaf\Shared\Domain\Exception\DomainException;

final class AdNotFound extends DomainException
{
    public static function withId(AdId $id): self
    {
        return new self(sprintf('Ad %d does not exist.', $id->value));
    }
}
