<?php

declare(strict_types=1);

namespace Elaf\Shared\Application\Validation;

use Elaf\Shared\Domain\Exception\InvalidSlug;
use Elaf\Shared\Domain\Text\OptionalText;
use Elaf\Shared\Domain\Text\Slug;

/**
 * Field checks shared by the lightweight models' input validators. Each
 * failure is an InvalidInput naming the field.
 */
final class Fields
{
    public static function required(string $field, string $value, int $max): string
    {
        $value = trim($value);

        if ($value === '') {
            throw InvalidInput::required($field);
        }

        if (mb_strlen($value) > $max) {
            throw InvalidInput::tooLong($field, $max);
        }

        return $value;
    }

    public static function optional(string $field, ?string $value, int $max): ?string
    {
        $value = OptionalText::normalize($value);

        if ($value !== null && mb_strlen($value) > $max) {
            throw InvalidInput::tooLong($field, $max);
        }

        return $value;
    }

    public static function httpUrl(string $field, ?string $value): ?string
    {
        $value = self::optional($field, $value, 2048);

        if ($value !== null && ! OptionalText::isHttpUrl($value)) {
            throw InvalidInput::invalidUrl($field);
        }

        return $value;
    }

    /**
     * The given slug, or one derived from $fallbackText when it is blank.
     */
    public static function slug(string $field, ?string $slug, string $fallbackText): Slug
    {
        $slug = OptionalText::normalize($slug);

        try {
            return $slug === null ? Slug::fromText($fallbackText) : Slug::fromString($slug);
        } catch (InvalidSlug $e) {
            throw InvalidInput::invalidSlug($field, $e->getMessage());
        }
    }
}
