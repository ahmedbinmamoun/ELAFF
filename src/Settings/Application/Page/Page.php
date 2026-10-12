<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Page;

use Elaf\Settings\Domain\Page\PageId;

final readonly class Page
{
    public function __construct(
        public PageId $id,
        public PageData $data,
    ) {}
}
