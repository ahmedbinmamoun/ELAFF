<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Issue;

use Elaf\Publishing\Domain\Issue\IssueNumber;
use Elaf\Publishing\Domain\Issue\PublicationDate;

/**
 * Validated issue fields.
 */
final readonly class IssueDetails
{
    public function __construct(
        public IssueNumber $number,
        public PublicationDate $publishedOn,
        public ?string $title,
    ) {}
}
