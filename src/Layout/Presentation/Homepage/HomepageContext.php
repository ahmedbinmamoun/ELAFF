<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Homepage;

/**
 * Shared while one homepage renders: articles already shown are not repeated
 * by later blocks.
 */
final class HomepageContext
{
    /** @var array<int, true> */
    private array $shown = [];

    public function markShown(int ...$articleIds): void
    {
        foreach ($articleIds as $id) {
            $this->shown[$id] = true;
        }
    }

    /**
     * @return list<int>
     */
    public function shown(): array
    {
        return array_keys($this->shown);
    }
}
