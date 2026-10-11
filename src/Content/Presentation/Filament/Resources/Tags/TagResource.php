<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Tags;

use BackedEnum;
use Elaf\Content\Application\Tag\DeleteTag\DeleteTagCommand;
use Elaf\Content\Application\Tag\DeleteTag\DeleteTagHandler;
use Elaf\Content\Infrastructure\Persistence\Models\TagModel;
use Elaf\Content\Presentation\Filament\Resources\Tags\Pages\CreateTag;
use Elaf\Content\Presentation\Filament\Resources\Tags\Pages\EditTag;
use Elaf\Content\Presentation\Filament\Resources\Tags\Pages\ListTags;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseDeleteAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * FR-TAX-02. The slug always follows the name.
 *
 * @extends resource<TagModel>
 */
final class TagResource extends Resource
{
    protected static ?string $model = TagModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return (string) __('admin.tag.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.tag.plural');
    }

    public static function getNavigationGroup(): string
    {
        return (string) __('admin.navigation.taxonomy');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('admin.tag.fields.name'))->required()->maxLength(60),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(static fn (Builder $query) => $query->withCount('articles'))
            ->columns([
                TextColumn::make('name')->label(__('admin.tag.fields.name'))->searchable()->sortable(),
                TextColumn::make('slug')->label(__('admin.tag.fields.slug')),
                TextColumn::make('articles_count')->label(__('admin.tag.fields.articles_count'))->numeric()->sortable(),
            ])
            ->recordActions([EditAction::make(), self::deleteAction()]);
    }

    public static function deleteAction(): DeleteAction
    {
        return UseCaseDeleteAction::make(static fn (Model $record) => app(DeleteTagHandler::class)
            ->handle(new DeleteTagCommand(FormData::key($record))));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTags::route('/'),
            'create' => CreateTag::route('/create'),
            'edit' => EditTag::route('/{record}/edit'),
        ];
    }
}
