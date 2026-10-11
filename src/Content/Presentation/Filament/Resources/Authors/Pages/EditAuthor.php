<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Authors\Pages;

use Elaf\Content\Application\Author\UpdateAuthor\UpdateAuthorCommand;
use Elaf\Content\Application\Author\UpdateAuthor\UpdateAuthorHandler;
use Elaf\Content\Presentation\Filament\Resources\Authors\AuthorResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditAuthor extends EditRecord
{
    protected static string $resource = AuthorResource::class;

    protected function getHeaderActions(): array
    {
        return [AuthorResource::deleteAction()];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        UseCaseErrors::forForm(static fn () => app(UpdateAuthorHandler::class)
            ->handle(new UpdateAuthorCommand(FormData::key($record), AuthorResource::input($data))));

        return $record->refresh();
    }
}
