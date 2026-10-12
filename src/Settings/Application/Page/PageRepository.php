<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Page;

use Elaf\Settings\Domain\Page\PageId;
use Elaf\Shared\Domain\Text\Slug;

interface PageRepository
{
    public function create(PageData $data): PageId;

    public function update(PageId $id, PageData $data): void;

    public function find(PageId $id): ?Page;

    public function slugExists(Slug $slug, ?PageId $except = null): bool;

    public function delete(PageId $id): void;
}
