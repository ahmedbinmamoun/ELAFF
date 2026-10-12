<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Import;

use Elaf\Publishing\Domain\Issue\Exception\InvalidIssue;
use Elaf\Publishing\Domain\Issue\PublicationDate;

/**
 * Reads the publication date from an archive file name (FR-ISS-05):
 * "إيلاف_{day}_{month}_{year}.pdf", the month as a number or an Arabic month
 * name, digits Western or Arabic-Indic, separated by "_", "-", "." or spaces.
 */
final class IssueFileName
{
    private const MONTHS = [
        'يناير' => 1, 'فبراير' => 2, 'مارس' => 3, 'ابريل' => 4, 'مايو' => 5, 'يونيو' => 6, 'يونيه' => 6,
        'يوليو' => 7, 'يوليه' => 7, 'اغسطس' => 8, 'سبتمبر' => 9, 'اكتوبر' => 10, 'نوفمبر' => 11, 'ديسمبر' => 12,
    ];

    public static function date(string $fileName): ?PublicationDate
    {
        $name = self::normalize(basename($fileName));
        $pattern = '/^(?:ايلاف|elaf)?[\s_.\-]*(\d{1,2})[\s_.\-]+(\d{1,2}|\p{Arabic}+)[\s_.\-]+(\d{4})\.pdf$/iu';

        if (preg_match($pattern, $name, $m) !== 1) {
            return null;
        }

        $month = ctype_digit($m[2]) ? (int) $m[2] : (self::MONTHS[$m[2]] ?? 0);

        if ($month < 1 || $month > 12) {
            return null;
        }

        try {
            return PublicationDate::fromString(sprintf('%04d-%02d-%02d', (int) $m[3], $month, (int) $m[1]));
        } catch (InvalidIssue) {
            return null;
        }
    }

    /**
     * Arabic-Indic and Persian digits to Western; alef forms (أ إ آ) to ا.
     */
    private static function normalize(string $name): string
    {
        $name = strtr($name, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا',
        ]);

        return trim($name);
    }
}
