<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Ad;

use Elaf\Layout\Domain\Ad\Ad;
use Elaf\Layout\Domain\Ad\AdRepository;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Application\Transaction\Transaction;

final readonly class AdUnitOfWork
{
    public function __construct(
        public AdRepository $ads,
        private Transaction $transaction,
        private EventDispatcher $events,
    ) {}

    public function persist(Ad $ad): void
    {
        $this->transaction->run(function () use ($ad): void {
            $this->ads->save($ad);
            $this->events->dispatch(...$ad->releaseEvents());
        });
    }
}
