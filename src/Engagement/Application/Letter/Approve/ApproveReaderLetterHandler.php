<?php

declare(strict_types=1);

namespace Elaf\Engagement\Application\Letter\Approve;

use Elaf\Engagement\Application\Letter\LetterModeration;
use Elaf\Shared\Domain\Time\Clock;

final readonly class ApproveReaderLetterHandler
{
    public function __construct(
        private LetterModeration $letters,
        private Clock $clock,
    ) {}

    public function handle(ApproveReaderLetterCommand $command): void
    {
        $this->letters->requireModerator();
        $letter = $this->letters->load($command->letterId);
        $letter->approve($command->actorId, $this->clock);
        $this->letters->persist($letter);
    }
}
