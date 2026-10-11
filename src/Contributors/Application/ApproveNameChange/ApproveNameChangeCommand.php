<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application\ApproveNameChange;

final readonly class ApproveNameChangeCommand
{
    public function __construct(
        public int $actorId,
        public int $contributorId,
    ) {}
}
