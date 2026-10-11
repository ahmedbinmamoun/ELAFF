<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article\Exception;

use DateTimeImmutable;
use Elaf\Shared\Domain\Exception\DomainException;

final class ArticleWorkflowViolation extends DomainException
{
    private function __construct(
        public readonly string $reason,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function cannotApproveOwnArticle(): self
    {
        return new self('own_article_approval', 'Nobody can approve their own article.');
    }

    public static function returnNoteRequired(): self
    {
        return new self('return_note_required', 'Returning an article to its writer requires a note.');
    }

    public static function scheduleNotInFuture(DateTimeImmutable $at): self
    {
        return new self('schedule_in_past', sprintf('Publication can only be scheduled in the future, got %s.', $at->format(DATE_ATOM)));
    }

    public static function breakingExpiryNotInFuture(DateTimeImmutable $until): self
    {
        return new self('breaking_expiry_in_past', sprintf('Breaking news expiry must be in the future, got %s.', $until->format(DATE_ATOM)));
    }

    public static function publishedBeforeCreated(): self
    {
        return new self('published_before_created', 'An article cannot be published before it was created.');
    }
}
