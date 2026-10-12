<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Query;

final readonly class IssueDownload
{
    public function __construct(
        public int $id,
        public int $number,
        public string $pdfPath,
    ) {}
}
