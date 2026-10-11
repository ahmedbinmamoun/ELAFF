<?php

declare(strict_types=1);

namespace Elaf\Shared\Application\Html;

/**
 * Allow-list HTML cleaning (FR-ART-03). Every profile always strips scripts,
 * iframes/embeds, inline event handlers and javascript: URLs.
 */
interface HtmlSanitizer
{
    public function sanitize(string $html, SanitizerProfile $profile): string;
}
