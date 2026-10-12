<?php

declare(strict_types=1);

namespace Elaf\Publishing\Presentation\Filament\Issues\Pages;

use Elaf\Publishing\Presentation\Filament\Issues\IssueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListIssues extends ListRecords
{
    protected static string $resource = IssueResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
