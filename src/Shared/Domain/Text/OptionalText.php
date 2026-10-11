<?php

declare(strict_types=1);

namespace Elaf\Shared\Domain\Text;

/**
 * Optional free-text fields: surrounding whitespace is dropped and a blank
 * value means "not set".
 */
final class OptionalText
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    public static function isHttpUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false
            && in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true);
    }
}
