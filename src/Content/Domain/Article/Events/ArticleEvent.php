<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article\Events;

use DateTimeImmutable;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Event\DomainEvent;

/**
 * Every article event names who did it and when (audit log, FR-ART-08).
 */
abstract readonly class ArticleEvent implements DomainEvent
{
    public function __construct(
        public ArticleId $articleId,
        public UserId $actorId,
        public DateTimeImmutable $at,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->at;
    }

    /**
     * Event-specific data for the audit log, as scalars.
     *
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return [];
    }
}
