<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article\Exception;

use Elaf\Shared\Domain\Exception\DomainException;

final class ArticleNotPublishable extends DomainException
{
    /**
     * @param  list<string>  $missing
     */
    public function __construct(public readonly array $missing)
    {
        parent::__construct('Article cannot be published, missing: '.implode(', ', $missing).'.');
    }
}
