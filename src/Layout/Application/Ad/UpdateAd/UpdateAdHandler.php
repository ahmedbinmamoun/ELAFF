<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Ad\UpdateAd;

use Elaf\Layout\Application\Ad\AdFields;
use Elaf\Layout\Application\Ad\AdUnitOfWork;
use Elaf\Layout\Domain\Ad\AdId;
use Elaf\Shared\Domain\Time\Clock;

/**
 * Content, schedule and on/off in one save (PRD §8 UpdateAd).
 */
final readonly class UpdateAdHandler
{
    public function __construct(
        private AdFields $fields,
        private AdUnitOfWork $ads,
        private Clock $clock,
    ) {}

    public function handle(UpdateAdCommand $command): void
    {
        $this->fields->requireManage();
        $ad = $this->ads->ads->get(AdId::fromInt($command->adId));
        $input = $command->ad;
        $slot = $this->fields->slot($input);
        $creative = $this->fields->creative($input);
        $schedule = $this->fields->schedule($input);
        $actor = $command->actorId;

        $this->fields->guard(fn () => $ad->revise($input->title, $slot, $creative, $this->fields->target($input), $input->weight, $actor, $this->clock));
        $ad->reschedule($schedule, $actor, $this->clock);
        $input->isActive ? $ad->activate($actor, $this->clock) : $ad->deactivate($actor, $this->clock);

        $this->ads->persist($ad);
    }
}
