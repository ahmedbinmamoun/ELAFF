<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Ad\DeleteAd;

use Elaf\Layout\Application\Ad\AdFields;
use Elaf\Layout\Domain\Ad\AdId;
use Elaf\Layout\Domain\Ad\AdRepository;
use Elaf\Layout\Domain\Ad\Events\AdChanged;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Application\Transaction\Transaction;
use Elaf\Shared\Domain\Time\Clock;

final readonly class DeleteAdHandler
{
    public function __construct(
        private AdFields $fields,
        private AdRepository $ads,
        private Transaction $transaction,
        private EventDispatcher $events,
        private Clock $clock,
    ) {}

    public function handle(DeleteAdCommand $command): void
    {
        $this->fields->requireManage();
        $id = AdId::fromInt($command->adId);
        $this->ads->get($id);

        $this->transaction->run(function () use ($id, $command): void {
            $this->ads->delete($id);
            $this->events->dispatch(new AdChanged($id, 'deleted', $command->actorId, $this->clock->now()));
        });
    }
}
