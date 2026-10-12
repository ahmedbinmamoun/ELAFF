<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Ad\SelectForSlot;

use Elaf\Layout\Application\Ad\Query\AdCandidate;
use Elaf\Layout\Application\Ad\Query\AdQueries;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-ADS-02 rotation: among the ads running now in the slot, one is picked
 * at random in proportion to its weight. Null when none runs.
 */
final readonly class SelectAdForSlotHandler
{
    public function __construct(
        private AdQueries $ads,
        private Randomness $random,
        private Clock $clock,
    ) {}

    public function handle(string $slot): ?AdCandidate
    {
        $now = $this->clock->now();
        $running = array_values(array_filter(
            $this->ads->active(),
            static fn (AdCandidate $ad): bool => $ad->slot === $slot && $ad->runsAt($now),
        ));

        if ($running === []) {
            return null;
        }

        $total = array_sum(array_map(static fn (AdCandidate $ad): int => max(1, $ad->weight), $running));
        $ticket = $this->random->upTo($total);

        foreach ($running as $ad) {
            $ticket -= max(1, $ad->weight);

            if ($ticket <= 0) {
                return $ad;
            }
        }

        return $running[array_key_last($running)];
    }
}
