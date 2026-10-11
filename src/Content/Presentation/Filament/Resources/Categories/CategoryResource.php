<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Categories;

use BackedEnum;
use Elaf\Content\Application\Category\CategoryInput;
use Elaf\Content\Application\Category\DeleteCategory\DeleteCategoryCommand;
use Elaf\Content\Application\Category\DeleteCategory\DeleteCategoryHandler;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Content\Presentation\Filament\Resources\Categories\Pages\CreateCategory;
use Elaf\Content\Presentation\Filament\Resources\Categories\Pages\EditCategory;
use Elaf\Content\Presentation\Filament\Resources\Categories\Pages\ListCategories;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseDeleteAction;
use Elaf\Shared\Presentation\Icons;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * FR-TAX-01.
 *
 * @extends resource<CategoryModel>
 */
final class CategoryResource extends Resource
{
    protected static ?string $model = CategoryModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return (string) __('admin.category.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.category.plural');
    }

    public static function getNavigationGroup(): string
    {
        return (string) __('admin.navigation.taxonomy');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->label(self::label('name'))->required()->maxLength(120),
                TextInput::make('slug')->label(self::label('slug'))->maxLength(150)->helperText(__('admin.article.hints.slug')),
                Select::make('parent_id')
                    ->label(self::label('parent'))
                    ->options(static fn (?CategoryModel $record): array => CategoryModel::query()
                        ->when($record !== null, static fn ($q) => $q->whereKeyNot($record?->id))
                        ->orderBy('sort_order')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),
                TextInput::make('sort_order')->label(self::label('sort_order'))->integer()->default(0),
                Select::make('icon')
                    ->label(self::label('icon'))
                    ->options(Icons::SECTION_ICONS),
                Textarea::make('description')->label(self::label('description'))->rows(3)->maxLength(1000)->columnSpanFull(),
                Toggle::make('is_visible')->label(self::label('is_visible'))->default(true),
                Toggle::make('is_open_to_contributors')->label(self::label('is_open_to_contributors')),
                TextInput::make('seo_title')->label(self::label('seo_title'))->maxLength(255),
                TextInput::make('seo_description')->label(self::label('seo_description'))->maxLength(500),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->modifyQueryUsing(static fn (Builder $query) => $query->with('parent')->withCount('articles'))
            ->columns([
                TextColumn::make('name')->label(self::label('name'))->searchable(),
                TextColumn::make('slug')->label(self::label('slug'))->toggleable(),
                TextColumn::make('parent.name')->label(self::label('parent')),
                TextColumn::make('articles_count')->label(self::label('articles_count'))->numeric(),
                TextColumn::make('sort_order')->label(self::label('sort_order'))->sortable(),
                IconColumn::make('is_visible')->label(self::label('is_visible'))->boolean(),
                IconColumn::make('is_open_to_contributors')->label(self::label('is_open_to_contributors'))->boolean(),
            ])
            ->recordActions([EditAction::make(), self::deleteAction()]);
    }

    public static function deleteAction(): DeleteAction
    {
        return UseCaseDeleteAction::make(static fn (Model $record) => app(DeleteCategoryHandler::class)
            ->handle(new DeleteCategoryCommand(FormData::key($record))));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function input(array $data): CategoryInput
    {
        return new CategoryInput(
            name: FormData::string($data, 'name'),
            slug: FormData::text($data, 'slug'),
            description: FormData::text($data, 'description'),
            parentId: FormData::int($data, 'parent_id'),
            sortOrder: FormData::int($data, 'sort_order') ?? 0,
            isVisible: FormData::bool($data, 'is_visible', true),
            isOpenToContributors: FormData::bool($data, 'is_open_to_contributors'),
            seoTitle: FormData::text($data, 'seo_title'),
            seoDescription: FormData::text($data, 'seo_description'),
            icon: FormData::text($data, 'icon'),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }

    private static function label(string $field): string
    {
        return (string) __('admin.category.fields.'.$field);
    }
}
