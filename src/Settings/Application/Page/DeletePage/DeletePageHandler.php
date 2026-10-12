<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Page\DeletePage;

use Elaf\Settings\Application\Page\PageAccess;
use Elaf\Settings\Application\Page\PageRepository;
use Elaf\Settings\Domain\Events\PageChanged;
use Elaf\Settings\Domain\Page\PageId;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Application\Validation\RecordNotFound;
use Elaf\Shared\Domain\Time\Clock;

/**
 * Menu items pointing at the page stop showing (the menu query drops them).
 */
final readonly class DeletePageHandler
{
    public function __construct(
        private PageAccess $access,
        private PageRepository $pages,
        private EventDispatcher $events,
        private Clock $clock,
    ) {}

    public function handle(DeletePageCommand $command): void
    {
        $this->access->require();
        $id = PageId::fromInt($command->pageId);
        $page = $this->pages->find($id) ?? throw RecordNotFound::of('Page', $command->pageId);

        $this->pages->delete($id);
        $this->events->dispatch(new PageChanged($id->value, $page->data->slug->value, $this->clock->now()));
    }
}
