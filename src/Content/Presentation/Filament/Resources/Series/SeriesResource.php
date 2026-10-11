<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Series;

use BackedEnum;
use Elaf\Content\Application\Series\DeleteSeries\DeleteSeriesCommand;
use Elaf\Content\Application\Series\DeleteSeries\DeleteSeriesHandler;
use Elaf\Content\Application\Series\SeriesInput;
use Elaf\Content\Infrastructure\Persistence\Models\SeriesModel;
use Elaf\Content\Presentation\Filament\Resources\Series\Pages\CreateSeries;
use Elaf\Content\Presentation\Filament\Resources\Series\Pages\EditSeries;
use Elaf\Content\Presentation\Filament\Resources\Series\Pages\ListSeries;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseDeleteAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * FR-TAX-03.
 *
 * @extends resource<SeriesModel>
 */
final class SeriesResource extends Resource
{
    protected static ?string $model = SeriesModel::class;

    protected static ?string $slug = 'series';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return (string) __('admin.series.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.series.plural');
    }

    public static function getNavigationGroup(): string
    {
        return (string) __('admin.navigation.taxonomy');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label(__('admin.series.fields.title'))->required()->maxLength(255),
            TextInput::make('slug')->label(__('admin.series.fields.slug'))->maxLength(150)->helperText(__('admin.article.hints.slug')),
            Textarea::make('description')->label(__('admin.series.fields.description'))->rows(3)->maxLength(2000),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('title')
            ->modifyQueryUsing(static fn (Builder $query) => $query->withCount('articles'))
            ->columns([
                TextColumn::make('title')->label(__('admin.series.fields.title'))->searchable(),
                TextColumn::make('slug')->label(__('admin.series.fields.slug')),
                TextColumn::make('articles_count')->label(__('admin.series.fields.articles_count'))->numeric(),
            ])
            ->recordActions([EditAction::make(), self::deleteAction()]);
    }

    public static function deleteAction(): DeleteAction
    {
        return UseCaseDeleteAction::make(static fn (Model $record) => app(DeleteSeriesHandler::class)
            ->handle(new DeleteSeriesCommand(FormData::key($record))));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function input(array $data): SeriesInput
    {
        return new SeriesInput(
            title: FormData::string($data, 'title'),
            slug: FormData::text($data, 'slug'),
            description: FormData::text($data, 'description'),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSeries::route('/'),
            'create' => CreateSeries::route('/create'),
            'edit' => EditSeries::route('/{record}/edit'),
        ];
    }
}
