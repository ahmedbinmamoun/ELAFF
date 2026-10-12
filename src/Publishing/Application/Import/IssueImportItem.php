<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Import;

use Elaf\Publishing\Application\Issue\IssueInput;

final readonly class IssueImportItem
{
    public function __construct(
        public string $path,
        public IssueInput $input,
    ) {}
}
