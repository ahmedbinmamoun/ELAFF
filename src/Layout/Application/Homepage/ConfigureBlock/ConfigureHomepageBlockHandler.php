<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Homepage\ConfigureBlock;

use Elaf\Layout\Application\Homepage\HomepageBlocks;
use Elaf\Shared\Application\Validation\RecordNotFound;

/**
 * FR-HOME-01: region, options and on/off of one block.
 */
final readonly class ConfigureHomepageBlockHandler
{
    public function __construct(private HomepageBlocks $homepage) {}

    public function handle(ConfigureHomepageBlockCommand $command): void
    {
        $this->homepage->requireManage();
        $this->homepage->blocks->find($command->blockId) ?? throw RecordNotFound::of('Homepage block', $command->blockId);
        $data = $this->homepage->validate($command->block);

        $this->homepage->change($command->actorId, fn () => $this->homepage->blocks->update($command->blockId, $data));
    }
}
