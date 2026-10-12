<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Update;

use Elaf\Identity\Domain\UserId;
use Elaf\Publishing\Application\Issue\IssueAccess;
use Elaf\Publishing\Application\Issue\IssueInputValidator;
use Elaf\Publishing\Application\Issue\IssuePdfStore;
use Elaf\Publishing\Application\Issue\IssueStorageLayout;
use Elaf\Publishing\Application\Issue\IssueUnitOfWork;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Shared\Domain\Time\Clock;
use Throwable;

/**
 * Corrects number, date or title; a replacement PDF is rendered again and
 * the old files are removed once the change is saved.
 */
final readonly class UpdateIssueHandler
{
    public function __construct(
        private IssueAccess $access,
        private IssueInputValidator $validator,
        private IssuePdfStore $pdfs,
        private IssueUnitOfWork $issues,
        private Clock $clock,
    ) {}

    public function handle(UpdateIssueCommand $command): void
    {
        $this->access->requireManage();
        $id = IssueId::fromInt($command->issueId);
        $actor = UserId::fromInt($command->actorId);
        $issue = $this->issues->load($id);
        $details = $this->validator->validate($command->input, $id);

        $issue->revise($details->number, $details->publishedOn, $details->title, $actor, $this->clock);

        if ($command->pdfPath === null) {
            $this->issues->persist($issue);

            return;
        }

        $old = $issue->file();
        $new = $this->pdfs->store($command->pdfPath, $details->number);

        try {
            $issue->replaceFile($new, $actor, $this->clock);
            $this->issues->persist($issue);
        } catch (Throwable $e) {
            if (IssueStorageLayout::directory($new) !== IssueStorageLayout::directory($old)) {
                $this->pdfs->discard($new);
            }

            throw $e;
        }

        if (IssueStorageLayout::directory($new) !== IssueStorageLayout::directory($old)) {
            $this->pdfs->discard($old);
        }
    }
}
