<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Articles\Pages;

use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Presentation\Filament\Resources\Articles\ArticleFormMapper;
use Elaf\Content\Presentation\Filament\Resources\Articles\ArticleResource;
use Elaf\Content\Presentation\Filament\Resources\Articles\ArticleWorkflowActions;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Read-only form, e.g. for a writer whose article is locked in review.
 */
final class ViewArticle extends ViewRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), ArticleWorkflowActions::group()];
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
