<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Articles\Pages;

use Elaf\Content\Application\Article\CreateArticleDraft\CreateArticleDraftCommand;
use Elaf\Content\Application\Article\CreateArticleDraft\CreateArticleDraftHandler;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Presentation\Filament\Resources\Articles\ArticleFormMapper;
use Elaf\Content\Presentation\Filament\Resources\Articles\ArticleResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $id = UseCaseErrors::forForm(static fn () => app(CreateArticleDraftHandler::class)->handle(
            new CreateArticleDraftCommand(FormData::actorId(), ArticleOrigin::Staff, ArticleFormMapper::toInput($data)),
        ), ['tags' => 'tags']);

        return ArticleModel::query()->findOrFail($id->value);
    }
}
