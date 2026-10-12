<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Page\UpdatePage;

use Elaf\Settings\Application\Page\PageInput;

final readonly class UpdatePageCommand
{
    public function __construct(
        public int $pageId,
        public PageInput $page,
    ) {}
}
