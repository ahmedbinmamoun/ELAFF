<?php

declare(strict_types=1);

namespace Elaf\Contributors\Presentation\Filament\Resources\MyArticles\Pages;

use Elaf\Content\Application\Article\CreateArticleDraft\CreateArticleDraftCommand;
use Elaf\Content\Application\Article\CreateArticleDraft\CreateArticleDraftHandler;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Presentation\Filament\Resources\Articles\ArticleFormMapper;
use Elaf\Contributors\Presentation\Filament\Resources\MyArticles\MyArticleResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * "New Article": always a contributor draft; the byline is set by the use case.
 */
final class CreateMyArticle extends CreateRecord
{
    protected static string $resource = MyArticleResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $id = UseCaseErrors::forForm(static fn () => app(CreateArticleDraftHandler::class)->handle(new CreateArticleDraftCommand(
            FormData::actorId(),
            ArticleOrigin::Contributor,
            ArticleFormMapper::toInput([...$data, 'source_name' => (string) __('site.name')]),
        )));

        return ArticleModel::query()->findOrFail($id->value);
    }
}
