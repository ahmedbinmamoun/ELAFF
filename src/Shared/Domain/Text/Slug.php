<?php

declare(strict_types=1);

namespace Elaf\Shared\Domain\Text;

use Elaf\Shared\Domain\Exception\InvalidSlug;
use Stringable;

/**
 * URL slug that keeps Arabic letters (FR-ART-10): lower-case letters and
 * digits of any script separated by single hyphens.
 */
final readonly class Slug implements Stringable
{
    public const MAX_LENGTH = 150;

    private const PATTERN = '/^[\p{Ll}\p{Lo}\p{N}]+(?:-[\p{Ll}\p{Lo}\p{N}]+)*$/u';

    // Tashkeel (harakat, tanween, shadda, sukun, superscript alef) and tatweel.
    private const ARABIC_MARKS = '/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u';

    private function __construct(public string $value) {}

    public static function fromString(string $value): self
    {
        if (mb_strlen($value) > self::MAX_LENGTH || preg_match(self::PATTERN, $value) !== 1) {
            throw InvalidSlug::malformed($value);
        }

        return new self($value);
    }

    /**
     * Derives a slug from free text such as a title or a name.
     */
    public static function fromText(string $text): self
    {
        $slug = mb_strtolower((string) preg_replace(self::ARABIC_MARKS, '', $text));
        $slug = (string) preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug);
        $slug = trim($slug, '-');

        if (mb_strlen($slug) > self::MAX_LENGTH) {
            $slug = rtrim(mb_substr($slug, 0, self::MAX_LENGTH), '-');
        }

        if ($slug === '') {
            throw InvalidSlug::emptyText($text);
        }

        return self::fromString($slug);
    }

    public function equals(self $other): bool
    {
        return $other->value === $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
