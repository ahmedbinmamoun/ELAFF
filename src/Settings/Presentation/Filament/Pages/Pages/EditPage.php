<?php

declare(strict_types=1);

namespace Elaf\Settings\Presentation\Filament\Pages\Pages;

use Elaf\Settings\Application\Page\UpdatePage\UpdatePageCommand;
use Elaf\Settings\Application\Page\UpdatePage\UpdatePageHandler;
use Elaf\Settings\Presentation\Filament\Pages\PageResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [PageResource::deleteAction()];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        UseCaseErrors::forForm(static fn () => app(UpdatePageHandler::class)
            ->handle(new UpdatePageCommand(FormData::key($record), PageResource::input($data))));

        return $record->refresh();
    }
}
