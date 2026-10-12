<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Filament\Homepage\Pages;

use Elaf\Layout\Application\Homepage\AddBlock\AddHomepageBlockCommand;
use Elaf\Layout\Application\Homepage\AddBlock\AddHomepageBlockHandler;
use Elaf\Layout\Infrastructure\Persistence\HomepageBlockModel;
use Elaf\Layout\Presentation\Filament\Homepage\HomepageBlockResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateHomepageBlock extends CreateRecord
{
    protected static string $resource = HomepageBlockResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $id = UseCaseErrors::forForm(static fn () => app(AddHomepageBlockHandler::class)
            ->handle(new AddHomepageBlockCommand(FormData::actorId(), HomepageBlockResource::input($data))));

        return HomepageBlockModel::query()->findOrFail($id);
    }

    protected function getRedirectUrl(): string
    {
        return HomepageBlockResource::getUrl('index');
    }
}
