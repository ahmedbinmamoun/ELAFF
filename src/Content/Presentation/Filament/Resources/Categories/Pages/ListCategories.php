<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Categories\Pages;

use Elaf\Content\Presentation\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
