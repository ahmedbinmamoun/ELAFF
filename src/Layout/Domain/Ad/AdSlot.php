<?php

declare(strict_types=1);

namespace Elaf\Layout\Domain\Ad;

/**
 * Named places for ads, defined in code (FR-ADS-01). Each has a recommended
 * creative size; the page reserves that shape so an ad never shifts content.
 */
enum AdSlot: string
{
    case HeaderBanner = 'header_banner';
    case HomeBetweenSections = 'home_between_sections';
    case ArticleSidebar = 'article_sidebar';
    case ArticleInline = 'article_inline';
    case Footer = 'footer';

    public function width(): int
    {
        return match ($this) {
            self::HeaderBanner, self::ArticleInline, self::Footer => 728,
            self::HomeBetweenSections => 970,
            self::ArticleSidebar => 300,
        };
    }

    public function height(): int
    {
        return match ($this) {
            self::HeaderBanner, self::ArticleInline, self::Footer => 90,
            self::HomeBetweenSections, self::ArticleSidebar => 250,
        };
    }
}
