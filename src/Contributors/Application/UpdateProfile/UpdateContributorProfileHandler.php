<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application\UpdateProfile;

use Elaf\Contributors\Application\ContributorInput;
use Elaf\Contributors\Domain\ContributorRepository;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Application\Transaction\Transaction;
use Elaf\Shared\Domain\Time\Clock;

/**
 * Bio, phone and photo change at once; a new display name waits for an
 * editor (PRD §2.1).
 */
final readonly class UpdateContributorProfileHandler
{
    public function __construct(
        private ContributorRepository $contributors,
        private Transaction $transaction,
        private EventDispatcher $events,
        private Clock $clock,
    ) {}

    public function handle(UpdateContributorProfileCommand $command): void
    {
        $name = ContributorInput::name($command->name);
        $profile = ContributorInput::profile($command->bio, $command->phone, $command->photoPath);

        $this->transaction->run(function () use ($command, $name, $profile): void {
            $contributor = $this->contributors->get(UserId::fromInt($command->actorId));
            $contributor->updateProfile($profile);
            $contributor->requestNameChange($name, $this->clock);
            $this->contributors->save($contributor);
            $this->events->dispatch(...$contributor->releaseEvents());
        });
    }
}
