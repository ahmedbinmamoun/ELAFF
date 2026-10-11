<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article\Exception;

use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Shared\Domain\Exception\DomainException;

final class IllegalArticleTransition extends DomainException
{
    public function __construct(
        public readonly ArticleStatus $from,
        public readonly ArticleStatus $to,
    ) {
        parent::__construct(sprintf('An article cannot move from "%s" to "%s".', $from->value, $to->value));
    }
}
