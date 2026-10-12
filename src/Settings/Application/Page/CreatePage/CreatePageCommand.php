<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Page\CreatePage;

use Elaf\Settings\Application\Page\PageInput;

final readonly class CreatePageCommand
{
    public function __construct(public PageInput $page) {}
}
