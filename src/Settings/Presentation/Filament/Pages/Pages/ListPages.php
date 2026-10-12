<?php

declare(strict_types=1);

namespace Elaf\Settings\Presentation\Filament\Pages\Pages;

use Elaf\Settings\Presentation\Filament\Pages\PageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
