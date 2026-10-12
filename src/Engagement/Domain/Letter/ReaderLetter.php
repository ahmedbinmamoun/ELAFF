<?php

declare(strict_types=1);

namespace Elaf\Engagement\Domain\Letter;

use DateTimeImmutable;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Engagement\Domain\Letter\Events\ReaderLetterApproved;
use Elaf\Engagement\Domain\Letter\Events\ReaderLetterConverted;
use Elaf\Engagement\Domain\Letter\Events\ReaderLetterReceived;
use Elaf\Engagement\Domain\Letter\Events\ReaderLetterRejected;
use Elaf\Engagement\Domain\Letter\Exception\InvalidLetter;
use Elaf\Engagement\Domain\Letter\Exception\LetterWorkflowViolation;
use Elaf\Shared\Domain\Event\RecordsEvents;
use Elaf\Shared\Domain\Time\Clock;

/**
 * A letter to "بريد القراء" (PRD §6.3, FR-LET-01..03). Plain text: never
 * rendered as HTML. The sender's IP is kept only as a keyed hash.
 */
final class ReaderLetter
{
    use RecordsEvents;

    public const MAX_TITLE = 150;

    public const MAX_BODY = 5000;

    private function __construct(
        private readonly ReaderLetterId $id,
        private readonly Sender $sender,
        private readonly string $title,
        private readonly string $body,
        private readonly string $ipHash,
        private readonly DateTimeImmutable $receivedAt,
        private LetterStatus $status = LetterStatus::Pending,
        private ?string $moderatorNote = null,
        private ?int $moderatedBy = null,
        private ?ArticleId $articleId = null,
    ) {}

    public static function submit(ReaderLetterId $id, Sender $sender, string $title, string $body, string $ipHash, Clock $clock): self
    {
        $title = trim($title);
        $body = trim(str_replace("\r\n", "\n", $body));

        if ($title === '' || mb_strlen($title) > self::MAX_TITLE) {
            throw InvalidLetter::because('title', 'letter_title', 'A title of at most 150 characters is required.');
        }

        if (mb_strlen($body) < 20 || mb_strlen($body) > self::MAX_BODY) {
            throw InvalidLetter::because('body', 'letter_body', 'The message must be 20 to 5000 characters.');
        }

        $letter = new self($id, $sender, $title, $body, $ipHash, $clock->now());
        $letter->recordThat(new ReaderLetterReceived($id, null, $clock->now()));

        return $letter;
    }

    /**
     * Rebuilds a persisted letter. Repositories only; records no events.
     */
    public static function reconstitute(
        ReaderLetterId $id,
        Sender $sender,
        string $title,
        string $body,
        string $ipHash,
        DateTimeImmutable $receivedAt,
        LetterStatus $status,
        ?string $moderatorNote,
        ?int $moderatedBy,
        ?ArticleId $articleId,
    ): self {
        return new self($id, $sender, $title, $body, $ipHash, $receivedAt, $status, $moderatorNote, $moderatedBy, $articleId);
    }

    public function approve(int $moderator, Clock $clock): void
    {
        $this->requirePending();
        $this->status = LetterStatus::Approved;
        $this->moderatedBy = $moderator;
        $this->recordThat(new ReaderLetterApproved($this->id, $moderator, $clock->now()));
    }

    public function reject(int $moderator, string $reason, Clock $clock): void
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw LetterWorkflowViolation::because('rejection_reason_required', 'A rejection needs a reason.');
        }

        $this->requirePending();
        $this->status = LetterStatus::Rejected;
        $this->moderatorNote = mb_substr($reason, 0, 1000);
        $this->moderatedBy = $moderator;
        $this->recordThat(new ReaderLetterRejected($this->id, $moderator, $clock->now()));
    }

    /**
     * FR-LET-02: an approved letter becomes (at most) one article draft.
     */
    public function markConverted(ArticleId $article, int $moderator, Clock $clock): void
    {
        $this->requireConvertible();
        $this->articleId = $article;
        $this->recordThat(new ReaderLetterConverted($this->id, $moderator, $clock->now()));
    }

    /**
     * @throws LetterWorkflowViolation unless approved and not converted yet
     */
    public function requireConvertible(): void
    {
        if ($this->status !== LetterStatus::Approved) {
            throw LetterWorkflowViolation::because('letter_not_approved', 'Only an approved letter can become an article.');
        }

        if ($this->articleId !== null) {
            throw LetterWorkflowViolation::because('letter_already_converted', 'The letter is already an article.');
        }
    }

    public function id(): ReaderLetterId
    {
        return $this->id;
    }

    public function sender(): Sender
    {
        return $this->sender;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function ipHash(): string
    {
        return $this->ipHash;
    }

    public function receivedAt(): DateTimeImmutable
    {
        return $this->receivedAt;
    }

    public function status(): LetterStatus
    {
        return $this->status;
    }

    public function moderatorNote(): ?string
    {
        return $this->moderatorNote;
    }

    public function moderatedBy(): ?int
    {
        return $this->moderatedBy;
    }

    public function articleId(): ?ArticleId
    {
        return $this->articleId;
    }

    private function requirePending(): void
    {
        if ($this->status !== LetterStatus::Pending) {
            throw LetterWorkflowViolation::because('letter_already_moderated', 'The letter was already moderated.');
        }
    }
}
