<?php

declare(strict_types=1);

namespace Elaf\Publishing\Domain\Issue;

use DateTimeImmutable;
use Elaf\Identity\Domain\UserId;
use Elaf\Publishing\Domain\Issue\Events\IssueProcessed;
use Elaf\Publishing\Domain\Issue\Events\IssueProcessingFailed;
use Elaf\Publishing\Domain\Issue\Events\IssueProcessingRetried;
use Elaf\Publishing\Domain\Issue\Events\IssueRevised;
use Elaf\Publishing\Domain\Issue\Events\IssueUploaded;
use Elaf\Publishing\Domain\Issue\Exception\IllegalIssueTransition;
use Elaf\Publishing\Domain\Issue\Exception\InvalidIssue;
use Elaf\Shared\Domain\Event\RecordsEvents;
use Elaf\Shared\Domain\Time\Clock;

/**
 * A printed issue (PRD §6.2, FR-ISS-01, 02, 06): its PDF and the page images
 * rendered from it.
 */
final class Issue
{
    use RecordsEvents;

    public const MAX_TITLE_LENGTH = 200;

    public const MAX_ERROR_LENGTH = 1000;

    private function __construct(
        private readonly IssueId $id,
        private IssueNumber $number,
        private PublicationDate $publishedOn,
        private ?string $title,
        private IssueFile $file,
        private ProcessingStatus $status,
        private ?IssueRendering $rendering = null,
        private ?string $processingError = null,
        private ?DateTimeImmutable $processedAt = null,
    ) {}

    public static function upload(
        IssueId $id,
        IssueNumber $number,
        PublicationDate $publishedOn,
        ?string $title,
        IssueFile $file,
        ?UserId $by,
        Clock $clock,
    ): self {
        $issue = new self($id, $number, $publishedOn, self::cleanTitle($title), $file, ProcessingStatus::Pending);
        $issue->recordThat(new IssueUploaded($id, $by, $clock->now()));

        return $issue;
    }

    /**
     * Rebuilds a persisted issue. Repositories only; records no events.
     */
    public static function reconstitute(
        IssueId $id,
        IssueNumber $number,
        PublicationDate $publishedOn,
        ?string $title,
        IssueFile $file,
        ProcessingStatus $status,
        ?IssueRendering $rendering,
        ?string $processingError,
        ?DateTimeImmutable $processedAt,
    ): self {
        if (($status === ProcessingStatus::Ready) !== ($rendering !== null)) {
            throw InvalidIssue::because('rendering_mismatch', 'Only a ready issue has rendered pages.');
        }

        return new self($id, $number, $publishedOn, $title, $file, $status, $rendering, $processingError, $processedAt);
    }

    public function revise(IssueNumber $number, PublicationDate $publishedOn, ?string $title, UserId $by, Clock $clock): void
    {
        $title = self::cleanTitle($title);

        if ($number->equals($this->number) && $publishedOn->equals($this->publishedOn) && $title === $this->title) {
            return;
        }

        $this->number = $number;
        $this->publishedOn = $publishedOn;
        $this->title = $title;
        $this->recordThat(new IssueRevised($this->id, $by, $clock->now()));
    }

    /**
     * A new PDF throws away the old pages: the issue is processed again.
     */
    public function replaceFile(IssueFile $file, UserId $by, Clock $clock): void
    {
        if ($file->equals($this->file)) {
            return;
        }

        $this->file = $file;
        $this->status = ProcessingStatus::Pending;
        $this->rendering = null;
        $this->processingError = null;
        $this->processedAt = null;
        $this->recordThat(new IssueUploaded($this->id, $by, $clock->now()));
    }

    public function startProcessing(): void
    {
        $this->transitionTo(ProcessingStatus::Processing);
        $this->processingError = null;
    }

    public function markReady(IssueRendering $rendering, Clock $clock): void
    {
        $this->transitionTo(ProcessingStatus::Ready);
        $this->rendering = $rendering;
        $this->processedAt = $clock->now();
        $this->recordThat(new IssueProcessed($this->id, null, $clock->now()));
    }

    /**
     * @param  string  $reason  a short key the admin translates
     */
    public function markFailed(string $reason, Clock $clock): void
    {
        $reason = trim($reason) === '' ? 'unknown' : mb_substr(trim($reason), 0, self::MAX_ERROR_LENGTH);

        $this->transitionTo(ProcessingStatus::Failed);
        $this->processingError = $reason;
        $this->recordThat(new IssueProcessingFailed($this->id, $clock->now(), $reason));
    }

    public function retry(UserId $by, Clock $clock): void
    {
        $this->transitionTo(ProcessingStatus::Pending);
        $this->recordThat(new IssueProcessingRetried($this->id, $by, $clock->now()));
    }

    public function id(): IssueId
    {
        return $this->id;
    }

    public function number(): IssueNumber
    {
        return $this->number;
    }

    public function publishedOn(): PublicationDate
    {
        return $this->publishedOn;
    }

    public function title(): ?string
    {
        return $this->title;
    }

    public function file(): IssueFile
    {
        return $this->file;
    }

    public function status(): ProcessingStatus
    {
        return $this->status;
    }

    public function rendering(): ?IssueRendering
    {
        return $this->rendering;
    }

    public function pageCount(): ?int
    {
        return $this->rendering?->pageCount();
    }

    public function processingError(): ?string
    {
        return $this->processingError;
    }

    public function processedAt(): ?DateTimeImmutable
    {
        return $this->processedAt;
    }

    private function transitionTo(ProcessingStatus $next): void
    {
        if (! $this->status->canTransitionTo($next)) {
            throw new IllegalIssueTransition($this->status, $next);
        }

        $this->status = $next;
    }

    private static function cleanTitle(?string $title): ?string
    {
        $title = $title === null ? '' : trim($title);

        if (mb_strlen($title) > self::MAX_TITLE_LENGTH) {
            throw InvalidIssue::because('title_too_long', sprintf('Issue title must be at most %d characters.', self::MAX_TITLE_LENGTH));
        }

        return $title === '' ? null : $title;
    }
}
