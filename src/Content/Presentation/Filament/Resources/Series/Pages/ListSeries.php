<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Series\Pages;

use Elaf\Content\Presentation\Filament\Resources\Series\SeriesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListSeries extends ListRecords
{
    protected static string $resource = SeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
