<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Page\DeletePage;

final readonly class DeletePageCommand
{
    public function __construct(public int $pageId) {}
}
