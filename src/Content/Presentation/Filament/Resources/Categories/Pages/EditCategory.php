<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Categories\Pages;

use Elaf\Content\Application\Category\UpdateCategory\UpdateCategoryCommand;
use Elaf\Content\Application\Category\UpdateCategory\UpdateCategoryHandler;
use Elaf\Content\Presentation\Filament\Resources\Categories\CategoryResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CategoryResource::deleteAction()];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        UseCaseErrors::forForm(static fn () => app(UpdateCategoryHandler::class)
            ->handle(new UpdateCategoryCommand(FormData::key($record), CategoryResource::input($data))));

        return $record->refresh();
    }
}
