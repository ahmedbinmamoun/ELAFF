<?php

declare(strict_types=1);

namespace Elaf\Contributors\Domain\Exception;

use Elaf\Contributors\Domain\ContributorStatus;
use Elaf\Shared\Domain\Exception\DomainException;

final class IllegalContributorTransition extends DomainException
{
    public function __construct(
        public readonly ContributorStatus $from,
        public readonly ContributorStatus $to,
    ) {
        parent::__construct(sprintf('A contributor cannot move from "%s" to "%s".', $from->value, $to->value));
    }
}
