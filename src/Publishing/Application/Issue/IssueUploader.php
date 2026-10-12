<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Issue;

use Elaf\Identity\Domain\UserId;
use Elaf\Publishing\Domain\Issue\Issue;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Shared\Domain\Time\Clock;
use Throwable;

/**
 * Creates a pending issue from a local PDF. Shared by the admin upload and
 * the bulk import; the callers decide who may do it.
 */
final readonly class IssueUploader
{
    public function __construct(
        private IssueInputValidator $validator,
        private IssuePdfStore $pdfs,
        private IssueUnitOfWork $issues,
        private Clock $clock,
    ) {}

    public function upload(IssueInput $input, string $localPdf, ?UserId $by): IssueId
    {
        $details = $this->validator->validate($input);
        $file = $this->pdfs->store($localPdf, $details->number);

        try {
            $issue = Issue::upload($this->issues->nextId(), $details->number, $details->publishedOn, $details->title, $file, $by, $this->clock);
            $this->issues->persist($issue);
        } catch (Throwable $e) {
            $this->pdfs->discard($file);

            throw $e;
        }

        return $issue->id();
    }
}
