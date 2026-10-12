<?php

declare(strict_types=1);

namespace Elaf\Engagement\Application\Letter\Reject;

final readonly class RejectReaderLetterCommand
{
    public function __construct(
        public int $actorId,
        public int $letterId,
        public string $reason,
    ) {}
}
