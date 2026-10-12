<?php

declare(strict_types=1);

namespace Elaf\Engagement\Domain\Letter\Exception;

use Elaf\Engagement\Domain\Letter\ReaderLetterId;
use Elaf\Shared\Domain\Exception\DomainException;

final class LetterNotFound extends DomainException
{
    public static function withId(ReaderLetterId $id): self
    {
        return new self(sprintf('Reader letter %d does not exist.', $id->value));
    }
}
