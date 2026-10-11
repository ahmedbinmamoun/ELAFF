<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article;

/**
 * Editorial workflow (FR-ART-04, PRD §6.1). The transition table lives here so
 * the aggregate and the admin UI share one source of truth.
 */
enum ArticleStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Returned = 'returned';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Unpublished = 'unpublished';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft, self::Returned => [self::InReview],
            self::InReview => [self::Draft, self::Approved, self::Returned],
            self::Approved => [self::Published, self::Scheduled],
            self::Scheduled => [self::Published, self::Scheduled],
            self::Published => [self::Unpublished],
            self::Unpublished => [self::Published],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /**
     * States in which the author (writer or contributor) may still edit.
     */
    public function isEditableByAuthor(): bool
    {
        return $this === self::Draft || $this === self::Returned;
    }

    public function isLive(): bool
    {
        return $this === self::Published;
    }
}
