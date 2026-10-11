<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\WithdrawSubmission;

final readonly class WithdrawSubmissionCommand
{
    public function __construct(
        public int $actorId,
        public int $articleId,
    ) {}
}
