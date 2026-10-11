<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article;

use Elaf\Content\Domain\Article\Exception\InvalidArticleContent;
use Elaf\Shared\Domain\Text\OptionalText;

/**
 * Where the story comes from, e.g. "إيلاف", "رويترز", "نقلاً عن …".
 */
final readonly class ArticleSource
{
    public const MAX_LENGTH = 120;

    public string $name;

    public ?string $url;

    public function __construct(string $name, ?string $url = null)
    {
        $name = trim($name);

        if ($name === '') {
            throw InvalidArticleContent::emptyField('source');
        }

        if (mb_strlen($name) > self::MAX_LENGTH) {
            throw InvalidArticleContent::tooLong('source', self::MAX_LENGTH);
        }

        $url = OptionalText::normalize($url);

        if ($url !== null && ! OptionalText::isHttpUrl($url)) {
            throw InvalidArticleContent::invalidUrl('source url', $url);
        }

        $this->name = $name;
        $this->url = $url;
    }
}
