<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Import;

use Elaf\Publishing\Application\Issue\IssueUploader;
use Elaf\Publishing\Domain\Issue\IssueNumber;
use Elaf\Publishing\Domain\Issue\IssueRepository;
use Elaf\Shared\Application\Validation\InvalidInput;

/**
 * FR-ISS-05: uploads every planned PDF as a pending issue; rendering is
 * queued like an admin upload. Issues whose number exists are skipped, so
 * an interrupted import can simply be run again.
 *
 * Runs from the server's command line (trusted, no signed-in user): the
 * events carry no actor.
 */
final readonly class ImportIssuesFromDirectoryHandler
{
    public function __construct(
        private IssueRepository $issues,
        private IssueUploader $uploader,
    ) {}

    public function handle(IssueImportPlan $plan): IssueImportReport
    {
        $imported = [];
        $skipped = [];
        $failed = [];

        foreach ($plan->items as $item) {
            $name = basename($item->path);

            if ($item->input->number > 0 && $this->issues->findByNumber(new IssueNumber($item->input->number)) !== null) {
                $skipped[$name] = $item->input->number;

                continue;
            }

            try {
                $this->uploader->upload($item->input, $item->path, null);
                $imported[$name] = $item->input->number;
            } catch (InvalidInput $e) {
                $failed[$name] = $e->reason;
            }
        }

        return new IssueImportReport($imported, $skipped, $failed);
    }
}
