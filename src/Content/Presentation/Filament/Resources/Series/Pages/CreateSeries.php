<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Series\Pages;

use Elaf\Content\Application\Series\CreateSeries\CreateSeriesCommand;
use Elaf\Content\Application\Series\CreateSeries\CreateSeriesHandler;
use Elaf\Content\Infrastructure\Persistence\Models\SeriesModel;
use Elaf\Content\Presentation\Filament\Resources\Series\SeriesResource;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateSeries extends CreateRecord
{
    protected static string $resource = SeriesResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $id = UseCaseErrors::forForm(static fn () => app(CreateSeriesHandler::class)
            ->handle(new CreateSeriesCommand(SeriesResource::input($data))));

        return SeriesModel::query()->findOrFail($id->value);
    }
}
