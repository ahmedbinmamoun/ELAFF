<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * DATETIME/TIMESTAMP columns hold wall-clock time in the app timezone
 * (Africa/Khartoum). Values are converted on the way in and out so a
 * DateTimeImmutable in any timezone is stored as the same instant.
 */
final class LocalTime
{
    public static function toColumn(?DateTimeInterface $moment): ?DateTimeImmutable
    {
        return $moment === null ? null : DateTimeImmutable::createFromInterface($moment)->setTimezone(self::zone());
    }

    public static function fromColumn(?DateTimeInterface $value): ?DateTimeImmutable
    {
        return $value === null ? null : DateTimeImmutable::createFromInterface($value)->setTimezone(self::zone());
    }

    public static function format(DateTimeInterface $moment): string
    {
        return DateTimeImmutable::createFromInterface($moment)->setTimezone(self::zone())->format('Y-m-d H:i:s');
    }

    private static function zone(): DateTimeZone
    {
        return new DateTimeZone(date_default_timezone_get());
    }
}
