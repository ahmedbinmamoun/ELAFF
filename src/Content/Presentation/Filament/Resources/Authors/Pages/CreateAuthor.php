<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Authors\Pages;

use Elaf\Content\Application\Author\CreateAuthor\CreateAuthorCommand;
use Elaf\Content\Application\Author\CreateAuthor\CreateAuthorHandler;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Presentation\Filament\Resources\Authors\AuthorResource;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateAuthor extends CreateRecord
{
    protected static string $resource = AuthorResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $id = UseCaseErrors::forForm(static fn () => app(CreateAuthorHandler::class)
            ->handle(new CreateAuthorCommand(AuthorResource::input($data))));

        return AuthorModel::query()->findOrFail($id->value);
    }
}
