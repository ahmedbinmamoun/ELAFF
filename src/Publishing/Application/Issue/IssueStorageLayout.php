<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Issue;

use Elaf\Publishing\Domain\Issue\IssueFile;
use Elaf\Publishing\Domain\Issue\IssueNumber;

/**
 * Where an issue's files live:
 *
 *   issues/{number}/{checksum prefix}/elaf-{number}.pdf
 *                                    /pages/001.webp, 001-thumb.webp, …
 *                                    /social.jpg
 *
 * The checksum prefix changes when the PDF is replaced, so a URL always
 * points at the same bytes and can be cached for a long time.
 */
final class IssueStorageLayout
{
    public const FULL_WIDTH = 1100;

    public const THUMB_WIDTH = 320;

    public static function pdf(IssueNumber $number, string $checksum): string
    {
        return sprintf('issues/%d/%s/elaf-%d.pdf', $number->value, substr($checksum, 0, 12), $number->value);
    }

    public static function directory(IssueFile $file): string
    {
        return dirname($file->path);
    }

    public static function page(IssueFile $file, int $page): string
    {
        return sprintf('%s/pages/%03d.webp', self::directory($file), $page);
    }

    public static function thumb(IssueFile $file, int $page): string
    {
        return sprintf('%s/pages/%03d-thumb.webp', self::directory($file), $page);
    }

    public static function socialImage(IssueFile $file): string
    {
        return self::directory($file).'/social.jpg';
    }
}
