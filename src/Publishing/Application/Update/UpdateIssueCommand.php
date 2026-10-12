<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Update;

use Elaf\Publishing\Application\Issue\IssueInput;

final readonly class UpdateIssueCommand
{
    /**
     * @param  string|null  $pdfPath  local path of a replacement PDF, if any
     */
    public function __construct(
        public int $actorId,
        public int $issueId,
        public IssueInput $input,
        public ?string $pdfPath = null,
    ) {}
}
