<?php

declare(strict_types=1);

use Elaf\Shared\Presentation\ArabicDate;

it('writes Gregorian dates with Arabic month names and Western digits in the site timezone (PRD §1)', function (): void {
    $previous = date_default_timezone_get();
    date_default_timezone_set('Africa/Khartoum');

    $utc = new DateTimeImmutable('2026-10-11 22:30:00', new DateTimeZone('UTC'));

    expect(ArabicDate::date($utc))->toBe('12 أكتوبر 2026')
        ->and(ArabicDate::dateTime($utc))->toBe('12 أكتوبر 2026، 00:30')
        ->and(ArabicDate::withWeekday(new DateTimeImmutable('2026-11-05 10:00')))->toBe('الخميس 5 نوفمبر 2026');

    date_default_timezone_set($previous);
});
