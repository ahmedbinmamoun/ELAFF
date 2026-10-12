<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Ad\CreateAd;

use Elaf\Layout\Application\Ad\AdFields;
use Elaf\Layout\Application\Ad\AdUnitOfWork;
use Elaf\Layout\Domain\Ad\Ad;
use Elaf\Layout\Domain\Ad\AdId;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-ADS-01, 02 (PRD §8 CreateAd).
 */
final readonly class CreateAdHandler
{
    public function __construct(
        private AdFields $fields,
        private AdUnitOfWork $ads,
        private Clock $clock,
    ) {}

    public function handle(CreateAdCommand $command): AdId
    {
        $this->fields->requireManage();
        $input = $command->ad;
        $slot = $this->fields->slot($input);
        $creative = $this->fields->creative($input);
        $schedule = $this->fields->schedule($input);

        $ad = $this->fields->guard(fn (): Ad => Ad::create(
            $this->ads->ads->nextId(), $input->title, $slot, $creative, $this->fields->target($input), $schedule,
            $input->weight, $input->isActive, $command->actorId, $this->clock,
        ));
        $this->ads->persist($ad);

        return $ad->id();
    }
}
