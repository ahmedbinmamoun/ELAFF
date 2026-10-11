<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Articles\Pages;

use Elaf\Content\Application\Article\UpdateArticle\UpdateArticleCommand;
use Elaf\Content\Application\Article\UpdateArticle\UpdateArticleHandler;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Presentation\Filament\Resources\Articles\ArticleFormMapper;
use Elaf\Content\Presentation\Filament\Resources\Articles\ArticleResource;
use Elaf\Content\Presentation\Filament\Resources\Articles\ArticleWorkflowActions;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [ArticleWorkflowActions::group()];
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
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        UseCaseErrors::forForm(static fn () => app(UpdateArticleHandler::class)->handle(
            new UpdateArticleCommand(FormData::actorId(), FormData::key($record), ArticleFormMapper::toInput($data)),
        ));

        return $record->refresh();
    }
}
