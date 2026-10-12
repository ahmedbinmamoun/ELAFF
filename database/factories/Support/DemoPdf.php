<?php

declare(strict_types=1);

namespace Database\Factories\Support;

use RuntimeException;

/**
 * Builds small, valid PDFs for seeds and tests without any PDF library:
 * each page shows one generated JPEG (or nothing). Never real issues.
 */
final class DemoPdf
{
    /** A3 portrait in points, the shape of a newspaper page. */
    private const PAGE_WIDTH = 842;

    private const PAGE_HEIGHT = 1191;

    /**
     * @param  int  $pages  at least 1
     */
    public static function blank(int $pages): string
    {
        return self::build(array_fill(0, max(1, $pages), null));
    }

    /**
     * Pages drawn with GD: a masthead band, the issue number in Latin digits,
     * a picture block and grey "text" columns.
     */
    public static function newspaper(int $number, int $pages, int $seed = 0): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return self::blank($pages);
        }

        $jpegs = [];

        for ($page = 1; $page <= max(1, $pages); $page++) {
            $jpegs[] = self::page($number, $page, $seed);
        }

        return self::build($jpegs);
    }

    /**
     * @param  list<string|null>  $jpegs  one entry per page; null = blank page
     */
    private static function build(array $jpegs): string
    {
        $objects = [];
        $kids = [];
        $next = 3;

        foreach ($jpegs as $jpeg) {
            $pageId = $next++;
            $kids[] = $pageId.' 0 R';

            if ($jpeg === null) {
                $objects[$pageId] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %d %d] >>', self::PAGE_WIDTH, self::PAGE_HEIGHT);

                continue;
            }

            $contentId = $next++;
            $imageId = $next++;
            $size = getimagesizefromstring($jpeg);

            if ($size === false) {
                throw new RuntimeException('Not a JPEG.');
            }

            $draw = sprintf('q %d 0 0 %d 0 0 cm /Im0 Do Q', self::PAGE_WIDTH, self::PAGE_HEIGHT);
            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %d %d] /Resources << /XObject << /Im0 %d 0 R >> >> /Contents %d 0 R >>',
                self::PAGE_WIDTH, self::PAGE_HEIGHT, $imageId, $contentId,
            );
            $objects[$contentId] = sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($draw), $draw);
            $objects[$imageId] = sprintf(
                "<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length %d >>\nstream\n%s\nendstream",
                $size[0], $size[1], strlen($jpeg), $jpeg,
            );
        }

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = sprintf('<< /Type /Pages /Kids [%s] /Count %d >>', implode(' ', $kids), count($kids));
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= sprintf("%d 0 obj\n%s\nendobj\n", $id, $body);
        }

        $xref = strlen($pdf);
        $pdf .= sprintf("xref\n0 %d\n0000000000 65535 f \n", count($objects) + 1);

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf.sprintf("trailer\n<< /Size %d /Root 1 0 R >>\nstartxref\n%d\n%%%%EOF\n", count($objects) + 1, $xref);
    }

    private static function page(int $number, int $page, int $seed): string
    {
        $width = 900;
        $height = 1273;
        $image = imagecreatetruecolor($width, $height);

        if ($image === false) {
            throw new RuntimeException('GD cannot create an image.');
        }

        $white = (int) imagecolorallocate($image, 255, 255, 255);
        $navy = (int) imagecolorallocate($image, 14, 42, 71);
        $red = (int) imagecolorallocate($image, 200, 16, 46);
        $grey = (int) imagecolorallocate($image, 205, 210, 218);
        $hue = abs($number * 37 + $page * 53 + $seed * 11) % 200;
        $picture = (int) imagecolorallocate($image, 40 + $hue % 120, 70 + ($hue * 3) % 120, 110 + ($hue * 7) % 120);

        imagefilledrectangle($image, 0, 0, $width, $height, $white);

        if ($page === 1) {
            imagefilledrectangle($image, 0, 0, $width, 150, $navy);
            imagefilledrectangle($image, 0, 150, $width, 160, $red);
            imagestring($image, 5, 40, 60, sprintf('ELAF  No. %d', $number), $white);
        } else {
            imagefilledrectangle($image, 0, 0, $width, 40, $navy);
            imagestring($image, 3, 30, 12, sprintf('ELAF %d  -  %d', $number, $page), $white);
        }

        $top = $page === 1 ? 200 : 80;
        imagefilledrectangle($image, 40, $top, $width - 40, $top + 380, $picture);

        for ($column = 0; $column < 4; $column++) {
            $left = 40 + $column * 210;

            for ($y = $top + 420; $y < $height - 40; $y += 22) {
                imagefilledrectangle($image, $left, $y, $left + 190 - (($y + $column) % 3) * 12, $y + 9, $grey);
            }
        }

        ob_start();
        imagejpeg($image, null, 70);
        $jpeg = (string) ob_get_clean();
        imagedestroy($image);

        return $jpeg;
    }
}
