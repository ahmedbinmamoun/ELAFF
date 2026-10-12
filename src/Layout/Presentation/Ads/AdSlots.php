<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Ads;

use Elaf\Layout\Application\Ad\Query\AdCandidate;
use Elaf\Layout\Application\Ad\SelectForSlot\SelectAdForSlotHandler;
use Elaf\Layout\Domain\Ad\AdSlot;

/**
 * One ad per slot per request (scoped): a slot asked twice shows the same ad.
 */
final class AdSlots
{
    /** @var array<string, AdCandidate|null> */
    private array $picked = [];

    public function __construct(private readonly SelectAdForSlotHandler $select) {}

    public function pick(AdSlot $slot): ?AdCandidate
    {
        if (! array_key_exists($slot->value, $this->picked)) {
            $this->picked[$slot->value] = $this->select->handle($slot->value);
        }

        return $this->picked[$slot->value];
    }
}
