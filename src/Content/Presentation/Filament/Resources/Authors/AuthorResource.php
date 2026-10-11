<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Authors;

use App\Models\User;
use BackedEnum;
use Elaf\Content\Application\Author\AuthorInput;
use Elaf\Content\Application\Author\DeleteAuthor\DeleteAuthorCommand;
use Elaf\Content\Application\Author\DeleteAuthor\DeleteAuthorHandler;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Presentation\Filament\Resources\Authors\Pages\CreateAuthor;
use Elaf\Content\Presentation\Filament\Resources\Authors\Pages\EditAuthor;
use Elaf\Content\Presentation\Filament\Resources\Authors\Pages\ListAuthors;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseDeleteAction;
use Elaf\Shared\Presentation\PublicMedia;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * FR-AUT-01/02.
 *
 * @extends resource<AuthorModel>
 */
final class AuthorResource extends Resource
{
    protected static ?string $model = AuthorModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?int $navigationSort = 50;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return (string) __('admin.author.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.author.plural');
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
                TextInput::make('title')->label(self::label('title'))->maxLength(120),
                Select::make('user_id')
                    ->label(self::label('user'))
                    ->options(static fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                Textarea::make('bio')->label(self::label('bio'))->rows(4)->maxLength(2000)->columnSpanFull(),
                FileUpload::make('photo_path')
                    ->label(self::label('photo'))
                    ->image()
                    ->avatar()
                    ->disk(PublicMedia::DISK)
                    ->directory('authors')
                    ->maxSize(2048),
                Toggle::make('is_columnist')->label(self::label('is_columnist')),
                KeyValue::make('social_links')
                    ->label(self::label('social_links'))
                    ->keyLabel(self::label('network'))
                    ->valueLabel(self::label('url'))
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(static fn (Builder $query) => $query->with('user')->withCount('articles'))
            ->columns([
                ImageColumn::make('photo_path')->label(self::label('photo'))->disk(PublicMedia::DISK)->circular(),
                TextColumn::make('name')->label(self::label('name'))->searchable()->sortable(),
                TextColumn::make('title')->label(self::label('title')),
                IconColumn::make('is_columnist')->label(self::label('is_columnist'))->boolean(),
                TextColumn::make('user.name')->label(self::label('user'))->toggleable(),
                TextColumn::make('articles_count')->label(self::label('articles_count'))->numeric()->sortable(),
            ])
            ->recordActions([EditAction::make(), self::deleteAction()]);
    }

    public static function deleteAction(): DeleteAction
    {
        return UseCaseDeleteAction::make(static fn (Model $record) => app(DeleteAuthorHandler::class)
            ->handle(new DeleteAuthorCommand(FormData::key($record))));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function input(array $data): AuthorInput
    {
        return new AuthorInput(
            name: FormData::string($data, 'name'),
            slug: FormData::text($data, 'slug'),
            title: FormData::text($data, 'title'),
            bio: FormData::text($data, 'bio'),
            photoPath: FormData::text($data, 'photo_path'),
            isColumnist: FormData::bool($data, 'is_columnist'),
            userId: FormData::int($data, 'user_id'),
            socialLinks: FormData::stringMap($data, 'social_links'),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuthors::route('/'),
            'create' => CreateAuthor::route('/create'),
            'edit' => EditAuthor::route('/{record}/edit'),
        ];
    }

    private static function label(string $field): string
    {
        return (string) __('admin.author.fields.'.$field);
    }
}
