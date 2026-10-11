<?php

declare(strict_types=1);

namespace Elaf\Contributors\Presentation\Filament\Resources\MyArticles;

use BackedEnum;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Content\Infrastructure\Persistence\Models\TagModel;
use Elaf\Content\Presentation\Filament\Editor\FigureBlock;
use Elaf\Content\Presentation\Filament\Resources\Articles\ArticleWorkflowActions;
use Elaf\Contributors\Presentation\Filament\Resources\MyArticles\Pages\CreateMyArticle;
use Elaf\Contributors\Presentation\Filament\Resources\MyArticles\Pages\EditMyArticle;
use Elaf\Contributors\Presentation\Filament\Resources\MyArticles\Pages\ListMyArticles;
use Elaf\Contributors\Presentation\Filament\Resources\MyArticles\Pages\ViewMyArticle;
use Elaf\Shared\Presentation\PublicMedia;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * "My Articles" in the contributor portal (FR-CON-05/06). Always scoped to
 * the signed-in contributor, whatever their permissions.
 *
 * @extends resource<ArticleModel>
 */
final class MyArticleResource extends Resource
{
    protected static ?string $model = ArticleModel::class;

    protected static ?string $slug = 'articles';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return (string) __('admin.article.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('contributors.my_articles');
    }

    /**
     * @return Builder<ArticleModel>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('created_by', Auth::id() ?? 0);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Callout::make(__('admin.article.fields.return_note'))
                ->description(static fn (?ArticleModel $record): ?string => $record?->return_note)
                ->warning()
                ->visible(static fn (?ArticleModel $record): bool => $record?->status === ArticleStatus::Returned->value && $record->return_note !== null)
                ->columnSpanFull(),
            Section::make(__('admin.article.sections.content'))->columnSpan(2)->schema([
                TextInput::make('title')->label(__('admin.article.fields.title'))->required()->maxLength(255),
                TextInput::make('subtitle')->label(__('admin.article.fields.subtitle'))->maxLength(500),
                Textarea::make('excerpt')->label(__('admin.article.fields.excerpt'))->rows(3)->maxLength(1000),
                RichEditor::make('body')
                    ->label(__('admin.article.fields.body'))
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'link'],
                        ['h2', 'h3', 'h4'],
                        ['blockquote', 'bulletList', 'orderedList'],
                        ['table', 'tableAddColumnAfter', 'tableAddRowAfter', 'tableDeleteColumn', 'tableDeleteRow', 'tableDelete'],
                        ['customBlocks'],
                        ['undo', 'redo'],
                    ])
                    ->customBlocks([FigureBlock::class]),
            ]),
            Group::make()->columnSpan(1)->schema([
                Section::make(__('admin.article.sections.classification'))->schema([
                    Select::make('category_id')
                        ->label(__('admin.article.fields.category'))
                        ->options(static fn (): array => CategoryModel::query()
                            ->where('is_open_to_contributors', true)
                            ->orderBy('sort_order')
                            ->pluck('name', 'id')
                            ->all())
                        ->required(),
                    TagsInput::make('tags')
                        ->label(__('admin.article.fields.tags'))
                        ->suggestions(static fn (): array => TagModel::query()->orderBy('name')->limit(500)->pluck('name')->all()),
                ]),
                Section::make(__('admin.article.sections.image'))->schema([
                    FileUpload::make('featured_image_path')
                        ->label(__('admin.article.fields.featured_image'))
                        ->image()
                        ->disk(PublicMedia::DISK)
                        ->directory('articles/featured')
                        ->maxSize(5120),
                    TextInput::make('featured_image_caption')->label(__('admin.article.fields.featured_image_caption'))->maxLength(500),
                    TextInput::make('featured_image_credit')->label(__('admin.article.fields.featured_image_credit'))->maxLength(255),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')->label(__('admin.article.fields.title'))->searchable()->limit(70),
                TextColumn::make('status')
                    ->label(__('admin.article.fields.status'))
                    ->badge()
                    ->formatStateUsing(static fn (string $state): string => (string) __('admin.status.'.$state)),
                TextColumn::make('return_note')->label(__('admin.article.fields.return_note'))->limit(60)->toggleable(),
                TextColumn::make('updated_at')->label(__('admin.article.fields.updated_at'))->since(),
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make()->hidden(static fn (ArticleModel $record): bool => Auth::user()?->can('update', $record) === true),
                ArticleWorkflowActions::submit(),
                ArticleWorkflowActions::withdraw(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMyArticles::route('/'),
            'create' => CreateMyArticle::route('/create'),
            'view' => ViewMyArticle::route('/{record}'),
            'edit' => EditMyArticle::route('/{record}/edit'),
        ];
    }
}
