<?php

declare(strict_types=1);

namespace Elaf\Shared\Presentation;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Gregorian dates with Arabic month names and Western digits (PRD §1),
 * in the site timezone.
 */
final class ArabicDate
{
    private const MONTHS = [
        1 => 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
        'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر',
    ];

    private const DAYS = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

    /** "11 أكتوبر 2026" */
    public static function date(DateTimeInterface $moment): string
    {
        $local = self::local($moment);

        return $local->format('j').' '.self::MONTHS[(int) $local->format('n')].' '.$local->format('Y');
    }

    /** "11 أكتوبر 2026، 14:05" */
    public static function dateTime(DateTimeInterface $moment): string
    {
        return self::date($moment).'، '.self::local($moment)->format('H:i');
    }

    /** "الأحد 11 أكتوبر 2026" */
    public static function withWeekday(DateTimeInterface $moment): string
    {
        return self::DAYS[(int) self::local($moment)->format('w')].' '.self::date($moment);
    }

    private static function local(DateTimeInterface $moment): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($moment)->setTimezone(new DateTimeZone(date_default_timezone_get()));
    }
}
