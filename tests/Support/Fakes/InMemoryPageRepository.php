<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Settings\Application\Page\Page;
use Elaf\Settings\Application\Page\PageData;
use Elaf\Settings\Application\Page\PageRepository;
use Elaf\Settings\Domain\Page\PageId;
use Elaf\Shared\Domain\Text\Slug;

final class InMemoryPageRepository implements PageRepository
{
    /** @var array<int, PageData> */
    private array $pages = [];

    private int $lastId = 0;

    public function create(PageData $data): PageId
    {
        $this->pages[++$this->lastId] = $data;

        return PageId::fromInt($this->lastId);
    }

    public function update(PageId $id, PageData $data): void
    {
        $this->pages[$id->value] = $data;
    }

    public function find(PageId $id): ?Page
    {
        return isset($this->pages[$id->value]) ? new Page($id, $this->pages[$id->value]) : null;
    }

    public function slugExists(Slug $slug, ?PageId $except = null): bool
    {
        foreach ($this->pages as $id => $page) {
            if ($page->slug->equals($slug) && $id !== $except?->value) {
                return true;
            }
        }

        return false;
    }

    public function delete(PageId $id): void
    {
        unset($this->pages[$id->value]);
    }
}
