<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Process;

final readonly class ProcessIssuePdfCommand
{
    public function __construct(public int $issueId) {}
}
