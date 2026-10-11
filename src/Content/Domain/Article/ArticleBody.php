<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article;

/**
 * Rich-text body. Must be built from HTML that already went through the
 * HtmlSanitizer port (FR-ART-03); the domain cannot sanitize by itself.
 */
final readonly class ArticleBody
{
    private const WORDS_PER_MINUTE = 200;

    private function __construct(public string $html) {}

    public static function fromSanitizedHtml(string $html): self
    {
        return new self(trim($html));
    }

    public static function empty(): self
    {
        return new self('');
    }

    public function isEmpty(): bool
    {
        return $this->plainText() === '' && ! str_contains($this->html, '<img');
    }

    public function plainText(): string
    {
        $text = html_entity_decode(strip_tags(str_replace('<', ' <', $this->html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    public function wordCount(): int
    {
        $text = $this->plainText();

        return $text === '' ? 0 : count(explode(' ', $text));
    }

    public function readingMinutes(): int
    {
        return max(1, (int) ceil($this->wordCount() / self::WORDS_PER_MINUTE));
    }

    public function equals(self $other): bool
    {
        return $other->html === $this->html;
    }
}
