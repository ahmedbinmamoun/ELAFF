<?php

declare(strict_types=1);

namespace Elaf\Layout\Domain\Ad;

use Elaf\Layout\Domain\Ad\Exception\AdNotFound;

/**
 * Write side only. Reads go through AdQueries.
 */
interface AdRepository
{
    public function nextId(): AdId;

    /**
     * @throws AdNotFound
     */
    public function get(AdId $id): Ad;

    public function save(Ad $ad): void;

    /**
     * Soft delete (PRD §7): click counts stay for the records.
     */
    public function delete(AdId $id): void;
}
