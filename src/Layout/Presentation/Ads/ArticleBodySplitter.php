<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Ads;

/**
 * Where the "article_inline" ad goes: after the Nth paragraph of the body,
 * only when more text follows.
 */
final class ArticleBodySplitter
{
    /**
     * @return array{0: string, 1: string} before and after the ad; the second is empty when the body is too short
     */
    public static function split(string $html, int $afterParagraphs = 3): array
    {
        $offset = 0;

        for ($i = 0; $i < $afterParagraphs; $i++) {
            $found = stripos($html, '</p>', $offset);

            if ($found === false) {
                return [$html, ''];
            }

            $offset = $found + 4;
        }

        $rest = substr($html, $offset);

        return trim(strip_tags($rest)) === '' ? [$html, ''] : [substr($html, 0, $offset), $rest];
    }
}
