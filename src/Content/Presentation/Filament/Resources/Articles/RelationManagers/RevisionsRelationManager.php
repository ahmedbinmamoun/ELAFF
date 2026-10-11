<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Articles\RelationManagers;

use Elaf\Content\Application\Article\RestoreArticleRevision\RestoreArticleRevisionCommand;
use Elaf\Content\Application\Article\RestoreArticleRevision\RestoreArticleRevisionHandler;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleRevisionModel;
use Elaf\Content\Presentation\Filament\Resources\Articles\ArticleResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

/**
 * FR-ART-08: content snapshots, restorable by editors.
 */
final class RevisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'revisions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return (string) __('admin.revisions.title');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(static fn (Builder $query) => $query->with('user'))
            ->columns([
                TextColumn::make('id')->label(__('admin.revisions.number')),
                TextColumn::make('snapshot.title')->label(__('admin.revisions.title_column'))->limit(60),
                TextColumn::make('user.name')->label(__('admin.revisions.user')),
                TextColumn::make('created_at')->label(__('admin.revisions.created_at'))->dateTime('Y-m-d H:i'),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label(__('admin.revisions.preview'))
                    ->icon(Heroicon::OutlinedEye)
                    ->modalSubmitAction(false)
                    ->modalHeading(static fn (ArticleRevisionModel $record): string => FormData::string($record->snapshot, 'title'))
                    ->modalContent(static fn (ArticleRevisionModel $record): HtmlString => new HtmlString(
                        '<div class="prose max-w-none" dir="rtl">'.str(FormData::string($record->snapshot, 'body'))->sanitizeHtml().'</div>',
                    )),
                Action::make('restore')
                    ->label(__('admin.revisions.restore'))
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(static fn (): bool => Gate::allows(ContentPermission::EditAnyArticle->value))
                    ->action(function (ArticleRevisionModel $record, Action $action): void {
                        $restored = UseCaseErrors::forAction(static fn () => app(RestoreArticleRevisionHandler::class)->handle(
                            new RestoreArticleRevisionCommand(FormData::actorId(), $record->id),
                        ));

                        if (! $restored) {
                            $action->halt();
                        }

                        Notification::make()->success()->title(__('admin.revisions.restored'))->send();
                        $action->redirect(ArticleResource::getUrl('edit', ['record' => $this->getOwnerRecord()]));
                    }),
            ]);
    }
}
