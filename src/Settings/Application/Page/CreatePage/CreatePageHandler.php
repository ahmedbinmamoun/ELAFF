<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Page\CreatePage;

use Elaf\Settings\Application\Page\PageAccess;
use Elaf\Settings\Application\Page\PageInputValidator;
use Elaf\Settings\Application\Page\PageRepository;
use Elaf\Settings\Domain\Events\PageChanged;
use Elaf\Settings\Domain\Page\PageId;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-PAG-01.
 */
final readonly class CreatePageHandler
{
    public function __construct(
        private PageAccess $access,
        private PageInputValidator $validator,
        private PageRepository $pages,
        private EventDispatcher $events,
        private Clock $clock,
    ) {}

    public function handle(CreatePageCommand $command): PageId
    {
        $this->access->require();
        $data = $this->validator->validate($command->page);
        $id = $this->pages->create($data);
        $this->events->dispatch(new PageChanged($id->value, $data->slug->value, $this->clock->now()));

        return $id;
    }
}
