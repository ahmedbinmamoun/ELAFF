<?php

declare(strict_types=1);

namespace Elaf\Shared\Domain\Exception;

final class InvalidSlug extends DomainException
{
    public static function malformed(string $value): self
    {
        return new self(sprintf('"%s" is not a valid slug.', $value));
    }

    public static function emptyText(string $text): self
    {
        return new self(sprintf('Cannot derive a slug from "%s".', $text));
    }
}
