<?php

declare(strict_types=1);

namespace Elaf\Shared\Application\Validation;

use RuntimeException;

final class RecordNotFound extends RuntimeException
{
    public static function of(string $type, int $id): self
    {
        return new self(sprintf('%s %d does not exist.', $type, $id));
    }
}
