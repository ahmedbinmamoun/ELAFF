<?php

declare(strict_types=1);

namespace Elaf\Shared\Application\Validation;

use InvalidArgumentException;

/**
 * A command field failed an application-level check (uniqueness, reference
 * to a missing record…). Presentation shows it on $field, translating
 * $reason with $params (lang/ar/errors.php); the message is for logs.
 */
final class InvalidInput extends InvalidArgumentException
{
    /**
     * @param  array<string, string|int>  $params
     */
    public function __construct(
        public readonly string $field,
        public readonly string $reason,
        string $message,
        public readonly array $params = [],
    ) {
        parent::__construct($message);
    }

    public static function required(string $field): self
    {
        return new self($field, 'required', sprintf('%s is required.', $field));
    }

    public static function tooLong(string $field, int $max): self
    {
        return new self($field, 'too_long', sprintf('%s must be at most %d characters.', $field, $max), ['max' => $max]);
    }

    public static function tooShort(string $field, int $min): self
    {
        return new self($field, 'too_short', sprintf('%s must be at least %d characters.', $field, $min), ['min' => $min]);
    }

    public static function invalidUrl(string $field): self
    {
        return new self($field, 'invalid_url', sprintf('%s must be an http(s) URL.', $field));
    }

    public static function invalidSlug(string $field, string $detail): self
    {
        return new self($field, 'invalid_slug', $detail);
    }

    public static function slugTaken(string $field, string $slug): self
    {
        return new self($field, 'slug_taken', sprintf('The slug "%s" is already in use.', $slug), ['slug' => $slug]);
    }

    public static function unknownReference(string $field, int $id): self
    {
        return new self($field, 'unknown_reference', sprintf('%s %d does not exist.', $field, $id), ['id' => $id]);
    }

    /**
     * A rule specific to one use case; $reason is its translation key.
     *
     * @param  array<string, string|int>  $params
     */
    public static function because(string $field, string $reason, string $message, array $params = []): self
    {
        return new self($field, $reason, $message, $params);
    }
}
