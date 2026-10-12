<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Query;

/**
 * Where the article appeared in print, when that issue is online
 * (PRD §10: "link to the print issue and page").
 */
final readonly class PrintRef
{
    public function __construct(
        public int $issueNumber,
        public int $page,
    ) {}
}
