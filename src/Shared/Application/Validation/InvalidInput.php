<?php

declare(strict_types=1);

namespace Elaf\Shared\Application\Validation;

use InvalidArgumentException;

/**
 * A command field failed an application-level check (uniqueness, reference
 * to a missing record…). Presentation maps it to a form error on $field.
 */
final class InvalidInput extends InvalidArgumentException
{
    public function __construct(
        public readonly string $field,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function required(string $field): self
    {
        return new self($field, sprintf('%s is required.', $field));
    }

    public static function tooLong(string $field, int $max): self
    {
        return new self($field, sprintf('%s must be at most %d characters.', $field, $max));
    }

    public static function slugTaken(string $field, string $slug): self
    {
        return new self($field, sprintf('The slug "%s" is already in use.', $slug));
    }

    public static function unknownReference(string $field, int $id): self
    {
        return new self($field, sprintf('%s %d does not exist.', $field, $id));
    }
}
