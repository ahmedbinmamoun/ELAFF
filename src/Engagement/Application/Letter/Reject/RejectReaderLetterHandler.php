<?php

declare(strict_types=1);

namespace Elaf\Engagement\Application\Letter\Reject;

use Elaf\Engagement\Application\Letter\LetterModeration;
use Elaf\Shared\Domain\Time\Clock;

final readonly class RejectReaderLetterHandler
{
    public function __construct(
        private LetterModeration $letters,
        private Clock $clock,
    ) {}

    public function handle(RejectReaderLetterCommand $command): void
    {
        $this->letters->requireModerator();
        $letter = $this->letters->load($command->letterId);
        $letter->reject($command->actorId, $command->reason, $this->clock);
        $this->letters->persist($letter);
    }
}
