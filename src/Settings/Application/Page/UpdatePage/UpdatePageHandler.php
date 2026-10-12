<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Page\UpdatePage;

use Elaf\Settings\Application\Page\PageAccess;
use Elaf\Settings\Application\Page\PageInputValidator;
use Elaf\Settings\Application\Page\PageRepository;
use Elaf\Settings\Domain\Events\PageChanged;
use Elaf\Settings\Domain\Page\PageId;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Application\Validation\RecordNotFound;
use Elaf\Shared\Domain\Time\Clock;

final readonly class UpdatePageHandler
{
    public function __construct(
        private PageAccess $access,
        private PageInputValidator $validator,
        private PageRepository $pages,
        private EventDispatcher $events,
        private Clock $clock,
    ) {}

    public function handle(UpdatePageCommand $command): void
    {
        $this->access->require();
        $id = PageId::fromInt($command->pageId);

        if ($this->pages->find($id) === null) {
            throw RecordNotFound::of('Page', $command->pageId);
        }

        $data = $this->validator->validate($command->page, $id);
        $this->pages->update($id, $data);
        $this->events->dispatch(new PageChanged($id->value, $data->slug->value, $this->clock->now()));
    }
}
