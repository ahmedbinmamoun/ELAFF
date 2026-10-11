<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article;

use Elaf\Content\Domain\Article\Exception\InvalidArticleContent;
use Elaf\Shared\Domain\Text\OptionalText;

final readonly class FeaturedImage
{
    public string $path;

    public ?string $caption;

    public ?string $credit;

    /**
     * @param  string  $path  location on the FileStorage port, not a URL
     */
    public function __construct(string $path, ?string $caption = null, ?string $credit = null)
    {
        $path = trim($path);

        if ($path === '') {
            throw InvalidArticleContent::emptyField('featured image path');
        }

        $this->path = $path;
        $this->caption = OptionalText::normalize($caption);
        $this->credit = OptionalText::normalize($credit);
    }
}
