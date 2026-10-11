<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Categories\Pages;

use Elaf\Content\Application\Category\CreateCategory\CreateCategoryCommand;
use Elaf\Content\Application\Category\CreateCategory\CreateCategoryHandler;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Content\Presentation\Filament\Resources\Categories\CategoryResource;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $id = UseCaseErrors::forForm(static fn () => app(CreateCategoryHandler::class)
            ->handle(new CreateCategoryCommand(CategoryResource::input($data))));

        return CategoryModel::query()->findOrFail($id->value);
    }
}
