<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Filament\Homepage\Pages;

use Elaf\Layout\Application\Homepage\ReorderBlocks\ReorderHomepageBlocksCommand;
use Elaf\Layout\Application\Homepage\ReorderBlocks\ReorderHomepageBlocksHandler;
use Elaf\Layout\Presentation\Filament\Homepage\HomepageBlockResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

final class ListHomepageBlocks extends ListRecords
{
    protected static string $resource = HomepageBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')->label(__('admin.homepage.preview'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(route('home'), shouldOpenInNewTab: true),
            CreateAction::make(),
        ];
    }

    /**
     * Drag and drop goes through ReorderHomepageBlocks, not Filament's own
     * column update.
     *
     * @param  array<int|string>  $order
     */
    public function reorderTable(array $order, int|string|null $draggedRecordKey = null): void
    {
        UseCaseErrors::forAction(static fn () => app(ReorderHomepageBlocksHandler::class)->handle(new ReorderHomepageBlocksCommand(
            FormData::actorId(),
            array_values(array_map(intval(...), $order)),
        )));
    }
}
