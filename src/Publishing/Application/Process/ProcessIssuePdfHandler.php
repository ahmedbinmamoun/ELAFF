<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Process;

use Elaf\Publishing\Application\Issue\IssueStorageLayout;
use Elaf\Publishing\Application\Issue\IssueUnitOfWork;
use Elaf\Publishing\Application\Port\ImageOptimizer;
use Elaf\Publishing\Application\Port\IssueProcessingError;
use Elaf\Publishing\Application\Port\PdfRasterizer;
use Elaf\Publishing\Domain\Issue\Exception\InvalidIssue;
use Elaf\Publishing\Domain\Issue\Issue;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Publishing\Domain\Issue\IssuePage;
use Elaf\Publishing\Domain\Issue\IssueRendering;
use Elaf\Publishing\Domain\Issue\ProcessingStatus;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-ISS-02, PRD §11 steps 3–5 (queued): renders every page to WebP (full +
 * thumbnail) and the social image from the cover.
 *
 * Only a pending issue is processed, so a job delivered twice does nothing
 * the second time. Rendering errors mark the issue failed with a reason key
 * (FR-ISS-06) and are not rethrown: retrying is the editor's decision.
 */
final readonly class ProcessIssuePdfHandler
{
    public function __construct(
        private IssueUnitOfWork $issues,
        private PdfRasterizer $rasterizer,
        private ImageOptimizer $images,
        private Clock $clock,
    ) {}

    public function handle(ProcessIssuePdfCommand $command): void
    {
        $issue = $this->issues->load(IssueId::fromInt($command->issueId));

        if ($issue->status() !== ProcessingStatus::Pending) {
            return;
        }

        $issue->startProcessing();
        $this->issues->persist($issue);

        try {
            $issue->markReady($this->render($issue), $this->clock);
        } catch (IssueProcessingError|InvalidIssue $e) {
            $issue->markFailed($e->reason, $this->clock);
        }

        $this->issues->persist($issue);
    }

    private function render(Issue $issue): IssueRendering
    {
        $file = $issue->file();
        $rasterized = $this->rasterizer->rasterize($file->path);

        try {
            if ($rasterized->images === []) {
                throw new IssueProcessingError('no_pages', 'The PDF has no pages.');
            }

            $pages = [];

            foreach ($rasterized->images as $index => $image) {
                $number = $index + 1;
                $full = $this->images->webp($image, IssueStorageLayout::page($file, $number), IssueStorageLayout::FULL_WIDTH);
                $this->images->webp($image, IssueStorageLayout::thumb($file, $number), IssueStorageLayout::THUMB_WIDTH);
                $pages[] = new IssuePage($number, IssueStorageLayout::page($file, $number), IssueStorageLayout::thumb($file, $number), $full->width, $full->height);
            }

            $this->images->socialCard($rasterized->images[0], IssueStorageLayout::socialImage($file));

            return new IssueRendering($pages, IssueStorageLayout::socialImage($file));
        } finally {
            $this->rasterizer->discard($rasterized);
        }
    }
}
