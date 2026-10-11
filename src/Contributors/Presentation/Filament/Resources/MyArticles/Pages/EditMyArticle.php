<?php

declare(strict_types=1);

namespace Elaf\Contributors\Presentation\Filament\Resources\MyArticles\Pages;

use Elaf\Content\Application\Article\UpdateArticle\UpdateArticleCommand;
use Elaf\Content\Application\Article\UpdateArticle\UpdateArticleHandler;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Presentation\Filament\Resources\Articles\ArticleFormMapper;
use Elaf\Content\Presentation\Filament\Resources\Articles\ArticleWorkflowActions;
use Elaf\Contributors\Presentation\Filament\Resources\MyArticles\MyArticleResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditMyArticle extends EditRecord
{
    protected static string $resource = MyArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [ArticleWorkflowActions::submit()];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        return $record instanceof ArticleModel ? ArticleFormMapper::fill($record) : $data;
    }

    /**
     * Fields the portal does not show keep their current values.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $current = $record instanceof ArticleModel ? ArticleFormMapper::fill($record) : [];

        UseCaseErrors::forForm(static fn () => app(UpdateArticleHandler::class)->handle(new UpdateArticleCommand(
            FormData::actorId(),
            FormData::key($record),
            ArticleFormMapper::toInput([...$current, ...$data]),
        )));

        return $record->refresh();
    }
}
