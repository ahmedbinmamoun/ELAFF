<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Articles;

use BackedEnum;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Presentation\Filament\Resources\Articles\Pages\CreateArticle;
use Elaf\Content\Presentation\Filament\Resources\Articles\Pages\EditArticle;
use Elaf\Content\Presentation\Filament\Resources\Articles\Pages\ListArticles;
use Elaf\Content\Presentation\Filament\Resources\Articles\Pages\ViewArticle;
use Elaf\Content\Presentation\Filament\Resources\Articles\RelationManagers\HistoryRelationManager;
use Elaf\Content\Presentation\Filament\Resources\Articles\RelationManagers\RevisionsRelationManager;
use Elaf\Content\Presentation\Filament\Resources\Articles\Schemas\ArticleForm;
use Elaf\Content\Presentation\Filament\Resources\Articles\Tables\ArticlesTable;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * @extends resource<ArticleModel>
 */
final class ArticleResource extends Resource
{
    protected static ?string $model = ArticleModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return (string) __('admin.article.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.article.plural');
    }

    public static function getNavigationGroup(): string
    {
        return (string) __('admin.navigation.content');
    }

    /**
     * Writers see only their own articles (PRD §2).
     *
     * @return Builder<ArticleModel>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['category', 'authors', 'writer']);

        return Gate::allows(ContentPermission::EditAnyArticle->value)
            ? $query
            : $query->where('created_by', Auth::id());
    }

    public static function form(Schema $schema): Schema
    {
        return ArticleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArticlesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RevisionsRelationManager::class,
            HistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'view' => ViewArticle::route('/{record}'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
