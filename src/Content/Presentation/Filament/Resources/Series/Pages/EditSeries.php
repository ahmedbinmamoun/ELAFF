<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Series\Pages;

use Elaf\Content\Application\Series\UpdateSeries\UpdateSeriesCommand;
use Elaf\Content\Application\Series\UpdateSeries\UpdateSeriesHandler;
use Elaf\Content\Presentation\Filament\Resources\Series\SeriesResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditSeries extends EditRecord
{
    protected static string $resource = SeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [SeriesResource::deleteAction()];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        UseCaseErrors::forForm(static fn () => app(UpdateSeriesHandler::class)
            ->handle(new UpdateSeriesCommand(FormData::key($record), SeriesResource::input($data))));

        return $record->refresh();
    }
}
