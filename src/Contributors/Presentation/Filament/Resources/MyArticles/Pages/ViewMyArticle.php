<?php

declare(strict_types=1);

namespace Elaf\Contributors\Presentation\Filament\Resources\MyArticles\Pages;

use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Presentation\Filament\Resources\Articles\ArticleFormMapper;
use Elaf\Content\Presentation\Filament\Resources\Articles\ArticleWorkflowActions;
use Elaf\Contributors\Presentation\Filament\Resources\MyArticles\MyArticleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Read-only once submitted (FR-CON-07, 09).
 */
final class ViewMyArticle extends ViewRecord
{
    protected static string $resource = MyArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), ArticleWorkflowActions::withdraw()];
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
}
