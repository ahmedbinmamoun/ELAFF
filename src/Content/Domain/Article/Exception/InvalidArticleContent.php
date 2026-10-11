<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article\Exception;

use Elaf\Shared\Domain\Exception\DomainException;

final class InvalidArticleContent extends DomainException
{
    public static function emptyField(string $field): self
    {
        return new self(sprintf('Article %s must not be empty.', $field));
    }

    public static function tooLong(string $field, int $max): self
    {
        return new self(sprintf('Article %s must be at most %d characters.', $field, $max));
    }

    public static function invalidUrl(string $field, string $url): self
    {
        return new self(sprintf('Article %s "%s" is not an http(s) URL.', $field, $url));
    }

    public static function notPositive(string $field, int $value): self
    {
        return new self(sprintf('Article %s must be a positive integer, got %d.', $field, $value));
    }

    public static function negative(string $field, int $value): self
    {
        return new self(sprintf('Article %s must not be negative, got %d.', $field, $value));
    }

    public static function duplicate(string $field): self
    {
        return new self(sprintf('Article %s must not contain duplicates.', $field));
    }

    public static function malformedSnapshot(string $reason): self
    {
        return new self('Article snapshot is malformed: '.$reason);
    }
}
