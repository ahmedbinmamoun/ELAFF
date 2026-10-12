<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Upload;

use Elaf\Identity\Domain\UserId;
use Elaf\Publishing\Application\Issue\IssueAccess;
use Elaf\Publishing\Application\Issue\IssueUploader;
use Elaf\Publishing\Domain\Issue\IssueId;

/**
 * FR-ISS-01, 02: stores the PDF and creates a pending issue; IssueUploaded
 * queues the rendering.
 */
final readonly class UploadIssueHandler
{
    public function __construct(
        private IssueAccess $access,
        private IssueUploader $uploader,
    ) {}

    public function handle(UploadIssueCommand $command): IssueId
    {
        $this->access->requireManage();

        return $this->uploader->upload($command->input, $command->pdfPath, UserId::fromInt($command->actorId));
    }
}
