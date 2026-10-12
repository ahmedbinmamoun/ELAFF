<?php

declare(strict_types=1);

namespace Elaf\Layout\Domain\Ad;

use Elaf\Layout\Domain\Ad\Exception\InvalidAd;

/**
 * What the ad shows: an uploaded image or an HTML snippet, never both
 * (FR-ADS-02). The HTML is stored as given; the use case sanitizes it first
 * unless an Admin saves it.
 */
final readonly class AdCreative
{
    public const MAX_HTML = 20_000;

    private function __construct(
        public ?string $imagePath,
        public ?string $html,
    ) {}

    public static function image(string $path): self
    {
        $path = trim($path);

        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/') || str_contains($path, '://')) {
            throw InvalidAd::because('ad_image_invalid', 'The ad image path is not valid.');
        }

        return new self($path, null);
    }

    public static function html(string $html): self
    {
        $html = trim($html);

        if ($html === '') {
            throw InvalidAd::because('ad_html_empty', 'The ad HTML is empty.');
        }

        if (mb_strlen($html) > self::MAX_HTML) {
            throw InvalidAd::because('ad_html_too_long', 'The ad HTML is too long.');
        }

        return new self(null, $html);
    }

    public function isImage(): bool
    {
        return $this->imagePath !== null;
    }

    public function equals(self $other): bool
    {
        return $other->imagePath === $this->imagePath && $other->html === $this->html;
    }
}
