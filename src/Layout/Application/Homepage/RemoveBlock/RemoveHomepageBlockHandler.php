<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Homepage\RemoveBlock;

use Elaf\Layout\Application\Homepage\HomepageBlocks;
use Elaf\Shared\Application\Validation\RecordNotFound;

final readonly class RemoveHomepageBlockHandler
{
    public function __construct(private HomepageBlocks $homepage) {}

    public function handle(RemoveHomepageBlockCommand $command): void
    {
        $this->homepage->requireManage();
        $this->homepage->blocks->find($command->blockId) ?? throw RecordNotFound::of('Homepage block', $command->blockId);

        $this->homepage->change($command->actorId, fn () => $this->homepage->blocks->remove($command->blockId));
    }
}
