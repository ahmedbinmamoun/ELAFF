<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article;

use Elaf\Content\Domain\Article\Exception\InvalidArticleContent;
use Stringable;

final readonly class Title implements Stringable
{
    public const MAX_LENGTH = 255;

    public string $value;

    public function __construct(string $value)
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));

        if ($value === '') {
            throw InvalidArticleContent::emptyField('title');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw InvalidArticleContent::tooLong('title', self::MAX_LENGTH);
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
