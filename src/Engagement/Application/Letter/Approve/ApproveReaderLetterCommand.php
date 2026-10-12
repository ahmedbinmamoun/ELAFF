<?php

declare(strict_types=1);

namespace Elaf\Engagement\Application\Letter\Approve;

final readonly class ApproveReaderLetterCommand
{
    public function __construct(
        public int $actorId,
        public int $letterId,
    ) {}
}
