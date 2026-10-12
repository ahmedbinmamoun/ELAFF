<?php

declare(strict_types=1);

namespace Elaf\Publishing\Domain\Issue\Exception;

use Elaf\Publishing\Domain\Issue\ProcessingStatus;
use Elaf\Shared\Domain\Exception\DomainException;

final class IllegalIssueTransition extends DomainException
{
    public function __construct(
        public readonly ProcessingStatus $from,
        public readonly ProcessingStatus $to,
    ) {
        parent::__construct(sprintf('An issue cannot move from "%s" to "%s".', $from->value, $to->value));
    }
}
