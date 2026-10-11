<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Tags\Pages;

use Elaf\Content\Presentation\Filament\Resources\Tags\TagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListTags extends ListRecords
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
