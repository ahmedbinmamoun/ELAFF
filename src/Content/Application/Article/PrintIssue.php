<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article;

final readonly class PrintIssue
{
    /**
     * @param  int|null  $pageCount  null until the PDF has been rendered
     */
    public function __construct(
        public int $number,
        public ?int $pageCount,
    ) {}
}
