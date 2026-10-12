<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Filament\Ads\Pages;

use Elaf\Layout\Presentation\Filament\Ads\AdResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListAds extends ListRecords
{
    protected static string $resource = AdResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
