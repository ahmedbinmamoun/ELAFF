<?php

declare(strict_types=1);

namespace Elaf\Contributors\Domain\Events;

use DateTimeImmutable;
use Elaf\Identity\Domain\UserId;

final readonly class ContributorRejected extends ContributorEvent
{
    public function __construct(
        UserId $contributorId,
        ?UserId $actorId,
        DateTimeImmutable $at,
        public string $reason,
    ) {
        parent::__construct($contributorId, $actorId, $at);
    }
}
