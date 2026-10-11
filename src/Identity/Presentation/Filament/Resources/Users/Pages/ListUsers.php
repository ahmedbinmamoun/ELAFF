<?php

declare(strict_types=1);

namespace Elaf\Identity\Presentation\Filament\Resources\Users\Pages;

use Elaf\Identity\Presentation\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
