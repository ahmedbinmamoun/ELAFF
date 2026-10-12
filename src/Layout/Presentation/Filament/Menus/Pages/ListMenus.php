<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Filament\Menus\Pages;

use Elaf\Layout\Presentation\Filament\Menus\MenuResource;
use Filament\Resources\Pages\ListRecords;

final class ListMenus extends ListRecords
{
    protected static string $resource = MenuResource::class;
}
