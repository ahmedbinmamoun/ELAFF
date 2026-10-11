<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Tags\Pages;

use Elaf\Content\Application\Tag\EnsureTagsExist\EnsureTagsExistCommand;
use Elaf\Content\Application\Tag\EnsureTagsExist\EnsureTagsExistHandler;
use Elaf\Content\Infrastructure\Persistence\Models\TagModel;
use Elaf\Content\Presentation\Filament\Resources\Tags\TagResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateTag extends CreateRecord
{
    protected static string $resource = TagResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $ids = UseCaseErrors::forForm(
            static fn () => app(EnsureTagsExistHandler::class)->handle(new EnsureTagsExistCommand([FormData::string($data, 'name')])),
            ['tags' => 'name'],
        );

        return TagModel::query()->findOrFail($ids[0]->value ?? 0);
    }
}
