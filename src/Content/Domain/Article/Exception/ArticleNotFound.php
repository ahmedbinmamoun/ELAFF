<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article\Exception;

use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Shared\Domain\Exception\DomainException;

final class ArticleNotFound extends DomainException
{
    public static function withId(ArticleId $id): self
    {
        return new self(sprintf('Article %d does not exist.', $id->value));
    }
}
