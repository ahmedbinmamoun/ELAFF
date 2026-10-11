<?php

declare(strict_types=1);

namespace Elaf\Shared\Domain\Exception;

final class InvalidIdentifier extends DomainException
{
    public static function mustBePositive(string $type, int $value): self
    {
        return new self(sprintf('%s must be a positive integer, got %d.', $type, $value));
    }
}
