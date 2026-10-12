<?php

declare(strict_types=1);

namespace Elaf\Settings\Domain\Events;

use DateTimeImmutable;
use Elaf\Shared\Domain\Event\DomainEvent;

/**
 * A static page was created, edited or deleted (caches, sitemap in Phase 6).
 */
final readonly class PageChanged implements DomainEvent
{
    public function __construct(
        public int $pageId,
        public string $slug,
        public DateTimeImmutable $at,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->at;
    }
}
