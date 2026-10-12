<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Upload;

use Elaf\Publishing\Application\Issue\IssueInput;

final readonly class UploadIssueCommand
{
    /**
     * @param  string  $pdfPath  local path of the uploaded file
     */
    public function __construct(
        public int $actorId,
        public IssueInput $input,
        public string $pdfPath,
    ) {}
}
