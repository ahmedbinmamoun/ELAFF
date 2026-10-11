<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application\VerifyEmail;

final readonly class VerifyContributorEmailCommand
{
    public function __construct(public int $contributorId) {}
}
