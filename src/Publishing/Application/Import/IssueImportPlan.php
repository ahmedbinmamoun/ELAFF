<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Import;

final readonly class IssueImportPlan
{
    /**
     * @param  list<IssueImportItem>  $items  in number order
     * @param  array<string, string>  $problems  file name => reason key
     */
    public function __construct(
        public array $items,
        public array $problems,
    ) {}
}
