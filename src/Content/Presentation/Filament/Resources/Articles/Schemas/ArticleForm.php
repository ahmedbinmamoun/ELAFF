<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Articles\Schemas;

use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Content\Infrastructure\Persistence\Models\SeriesModel;
use Elaf\Content\Infrastructure\Persistence\Models\TagModel;
use Elaf\Content\Presentation\Filament\Editor\FigureBlock;
use Elaf\Shared\Presentation\PublicMedia;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * FR-ART-01/02. Field names match the use-case input (see ArticleFormMapper).
 * Only input hints here; the use case validates.
 */
final class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make(__('admin.article.sections.content'))
                ->columnSpan(2)
                ->schema([
                    TextInput::make('kicker')->label(self::label('kicker'))->maxLength(255),
                    TextInput::make('title')->label(self::label('title'))->required()->maxLength(255),
                    TextInput::make('subtitle')->label(self::label('subtitle'))->maxLength(500),
                    Grid::make(2)->schema([
                        TextInput::make('dateline')->label(self::label('dateline'))->maxLength(100),
                        TextInput::make('slug')->label(self::label('slug'))->maxLength(150)->helperText(__('admin.article.hints.slug')),
                    ]),
                    Textarea::make('excerpt')->label(self::label('excerpt'))->rows(3)->maxLength(1000),
                    RichEditor::make('body')
                        ->label(self::label('body'))
                        ->toolbarButtons([
                            ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                            ['h2', 'h3', 'h4'],
                            ['blockquote', 'bulletList', 'orderedList', 'horizontalRule'],
                            ['table', 'tableAddColumnAfter', 'tableAddRowAfter', 'tableDeleteColumn', 'tableDeleteRow', 'tableToggleHeaderRow', 'tableDelete'],
                            ['customBlocks'],
                            ['undo', 'redo'],
                        ])
                        ->customBlocks([FigureBlock::class]),
                ]),
            Group::make()->columnSpan(1)->schema([
                Section::make(__('admin.article.sections.classification'))->schema([
                    Select::make('category_id')
                        ->label(self::label('category'))
                        ->options(static fn (): array => CategoryModel::query()->orderBy('sort_order')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),
                    Select::make('author_ids')
                        ->label(self::label('authors'))
                        ->helperText(__('admin.article.hints.authors'))
                        ->multiple()
                        ->options(static fn (): array => AuthorModel::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable(),
                    TagsInput::make('tags')
                        ->label(self::label('tags'))
                        ->helperText(__('admin.article.hints.tags'))
                        ->suggestions(static fn (): array => TagModel::query()->orderBy('name')->limit(500)->pluck('name')->all()),
                ]),
                Section::make(__('admin.article.sections.print'))->collapsed()->schema([
                    Select::make('print_section')
                        ->label(self::label('print_section'))
                        ->options(static function (): array {
                            $sections = config('elaf.print_sections');

                            $names = is_array($sections) ? array_values(array_filter($sections, is_string(...))) : [];

                            return array_combine($names, $names);
                        })
                        ->searchable(),
                ]),
                Section::make(__('admin.article.sections.source'))->schema([
                    TextInput::make('source_name')->label(self::label('source_name'))->required()->maxLength(120)->default('إيلاف'),
                    TextInput::make('source_url')->label(self::label('source_url'))->url()->maxLength(2048),
                ]),
                Section::make(__('admin.article.sections.image'))->schema([
                    FileUpload::make('featured_image_path')
                        ->label(self::label('featured_image'))
                        ->image()
                        ->disk(PublicMedia::DISK)
                        ->directory('articles/featured')
                        ->maxSize(5120),
                    TextInput::make('featured_image_caption')->label(self::label('featured_image_caption'))->maxLength(500),
                    TextInput::make('featured_image_credit')->label(self::label('featured_image_credit'))->maxLength(255),
                ]),
                Section::make(__('admin.article.sections.series'))->collapsed()->schema([
                    Select::make('series_id')
                        ->label(self::label('series'))
                        ->options(static fn (): array => SeriesModel::query()->orderBy('title')->pluck('title', 'id')->all())
                        ->searchable(),
                    TextInput::make('series_part')->label(self::label('series_part'))->integer()->minValue(1),
                ]),
                Section::make(__('admin.article.sections.seo'))->collapsed()->schema([
                    TextInput::make('seo_title')->label(self::label('seo_title'))->maxLength(255),
                    Textarea::make('seo_description')->label(self::label('seo_description'))->rows(2)->maxLength(500),
                    TextInput::make('canonical_url')->label(self::label('canonical_url'))->url()->maxLength(2048),
                ]),
            ]),
        ]);
    }

    private static function label(string $field): string
    {
        return (string) __('admin.article.fields.'.$field);
    }
}
