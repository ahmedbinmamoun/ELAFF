<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article\Exception;

use DateTimeImmutable;
use Elaf\Shared\Domain\Exception\DomainException;

final class ArticleWorkflowViolation extends DomainException
{
    public static function cannotApproveOwnArticle(): self
    {
        return new self('Nobody can approve their own article.');
    }

    public static function returnNoteRequired(): self
    {
        return new self('Returning an article to its writer requires a note.');
    }

    public static function scheduleNotInFuture(DateTimeImmutable $at): self
    {
        return new self(sprintf('Publication can only be scheduled in the future, got %s.', $at->format(DATE_ATOM)));
    }

    public static function breakingExpiryNotInFuture(DateTimeImmutable $until): self
    {
        return new self(sprintf('Breaking news expiry must be in the future, got %s.', $until->format(DATE_ATOM)));
    }

    public static function publishedBeforeCreated(): self
    {
        return new self('An article cannot be published before it was created.');
    }
}
