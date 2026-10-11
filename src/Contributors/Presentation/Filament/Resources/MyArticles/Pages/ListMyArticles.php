<?php

declare(strict_types=1);

namespace Elaf\Contributors\Presentation\Filament\Resources\MyArticles\Pages;

use Elaf\Contributors\Presentation\Filament\Resources\MyArticles\MyArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListMyArticles extends ListRecords
{
    protected static string $resource = MyArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
