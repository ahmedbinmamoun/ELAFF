<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Tags\Pages;

use Elaf\Content\Application\Tag\RenameTag\RenameTagCommand;
use Elaf\Content\Application\Tag\RenameTag\RenameTagHandler;
use Elaf\Content\Presentation\Filament\Resources\Tags\TagResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditTag extends EditRecord
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [TagResource::deleteAction()];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        UseCaseErrors::forForm(static fn () => app(RenameTagHandler::class)
            ->handle(new RenameTagCommand(FormData::key($record), FormData::string($data, 'name'))));

        return $record->refresh();
    }
}
