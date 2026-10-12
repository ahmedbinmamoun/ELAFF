<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Issue;

/**
 * Raw issue fields from a form or an import. $publishedOn is Y-m-d.
 */
final readonly class IssueInput
{
    public function __construct(
        public int $number,
        public string $publishedOn,
        public ?string $title = null,
    ) {}
}
