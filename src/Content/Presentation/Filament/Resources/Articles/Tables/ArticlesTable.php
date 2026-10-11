<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Articles\Tables;

use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Content\Presentation\Filament\Resources\Articles\ArticleWorkflowActions;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.article.fields.title'))
                    ->description(static fn (ArticleModel $record): ?string => $record->kicker)
                    ->searchable()
                    ->limit(70),
                TextColumn::make('status')
                    ->label(__('admin.article.fields.status'))
                    ->badge()
                    ->formatStateUsing(static fn (string $state): string => (string) __('admin.status.'.$state))
                    ->color(static fn (string $state): string => self::statusColor(ArticleStatus::from($state))),
                TextColumn::make('category.name')->label(__('admin.article.fields.category'))->toggleable(),
                TextColumn::make('authors.name')->label(__('admin.article.fields.authors'))->listWithLineBreaks()->toggleable(),
                TextColumn::make('writer.name')->label(__('admin.article.fields.writer'))->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_breaking')->label(__('admin.article.fields.breaking'))->boolean()->trueColor('danger')->toggleable(),
                IconColumn::make('is_featured')->label(__('admin.article.fields.featured'))->boolean()->toggleable(),
                TextColumn::make('published_at')->label(__('admin.article.fields.published_at'))->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('updated_at')->label(__('admin.article.fields.updated_at'))->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.article.fields.status'))
                    ->options(array_combine(
                        array_map(static fn (ArticleStatus $s): string => $s->value, ArticleStatus::cases()),
                        array_map(static fn (ArticleStatus $s): string => (string) __('admin.status.'.$s->value), ArticleStatus::cases()),
                    )),
                SelectFilter::make('category_id')
                    ->label(__('admin.article.fields.category'))
                    ->options(static fn (): array => CategoryModel::query()->orderBy('sort_order')->pluck('name', 'id')->all()),
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make()->hidden(static fn ($record): bool => auth()->user()?->can('update', $record) === true),
                ActionGroup::make(ArticleWorkflowActions::all()),
            ]);
    }

    private static function statusColor(ArticleStatus $status): string
    {
        return match ($status) {
            ArticleStatus::Draft => 'gray',
            ArticleStatus::InReview => 'info',
            ArticleStatus::Returned => 'warning',
            ArticleStatus::Approved, ArticleStatus::Scheduled => 'primary',
            ArticleStatus::Published => 'success',
            ArticleStatus::Unpublished => 'danger',
        };
    }
}
