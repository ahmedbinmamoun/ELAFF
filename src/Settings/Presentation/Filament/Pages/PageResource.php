<?php

declare(strict_types=1);

namespace Elaf\Settings\Presentation\Filament\Pages;

use BackedEnum;
use Elaf\Settings\Application\Page\DeletePage\DeletePageCommand;
use Elaf\Settings\Application\Page\DeletePage\DeletePageHandler;
use Elaf\Settings\Application\Page\PageInput;
use Elaf\Settings\Infrastructure\Persistence\PageModel;
use Elaf\Settings\Presentation\Filament\Pages\Pages\CreatePage;
use Elaf\Settings\Presentation\Filament\Pages\Pages\EditPage;
use Elaf\Settings\Presentation\Filament\Pages\Pages\ListPages;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseDeleteAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * FR-PAG-01: about, contact, advertise, privacy… Saved through the use cases.
 *
 * @extends resource<PageModel>
 */
final class PageResource extends Resource
{
    protected static ?string $model = PageModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 60;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return (string) __('admin.page.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.page.plural');
    }

    public static function getNavigationGroup(): string
    {
        return (string) __('admin.navigation.site');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('title')->label(self::label('title'))->required()->maxLength(150),
                TextInput::make('slug')->label(self::label('slug'))->maxLength(190)->helperText(__('admin.article.hints.slug')),
                RichEditor::make('body')->label(self::label('body'))->required()->columnSpanFull()
                    ->toolbarButtons([['bold', 'italic', 'link'], ['h2', 'h3'], ['blockquote', 'bulletList', 'orderedList'], ['table'], ['undo', 'redo']]),
                Toggle::make('is_published')->label(self::label('is_published'))->default(true),
            ]),
            Section::make(__('admin.page.seo'))->columns(2)->collapsed()->schema([
                TextInput::make('seo_title')->label(self::label('seo_title'))->maxLength(255),
                TextInput::make('seo_description')->label(self::label('seo_description'))->maxLength(500),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('title')
            ->columns([
                TextColumn::make('title')->label(self::label('title'))->searchable(),
                TextColumn::make('slug')->label(self::label('slug')),
                IconColumn::make('is_published')->label(self::label('is_published'))->boolean(),
                TextColumn::make('updated_at')->label(self::label('updated_at'))->dateTime('Y-m-d H:i'),
            ])
            ->recordActions([
                Action::make('open')->label(__('admin.page.open'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                    ->url(static fn (PageModel $record): string => route('page.show', $record->slug), shouldOpenInNewTab: true)
                    ->visible(static fn (PageModel $record): bool => $record->is_published),
                EditAction::make(),
                self::deleteAction(),
            ]);
    }

    public static function deleteAction(): DeleteAction
    {
        return UseCaseDeleteAction::make(static fn (Model $record) => app(DeletePageHandler::class)
            ->handle(new DeletePageCommand(FormData::key($record))));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function input(array $data): PageInput
    {
        return new PageInput(
            title: FormData::string($data, 'title'),
            slug: FormData::text($data, 'slug'),
            body: FormData::string($data, 'body'),
            isPublished: FormData::bool($data, 'is_published', true),
            seoTitle: FormData::text($data, 'seo_title'),
            seoDescription: FormData::text($data, 'seo_description'),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }

    private static function label(string $field): string
    {
        return (string) __('admin.page.fields.'.$field);
    }
}
