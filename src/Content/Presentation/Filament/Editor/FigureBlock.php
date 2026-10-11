<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Editor;

use Elaf\Shared\Presentation\PublicMedia;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;

/**
 * Inline image with alt text and caption (FR-ART-02). Stored as <figure>
 * by FigureBlockConverter.
 */
final class FigureBlock extends RichContentCustomBlock
{
    public const ID = 'figure';

    public static function getId(): string
    {
        return self::ID;
    }

    public static function getLabel(): string
    {
        return (string) __('admin.article.figure.label');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            FileUpload::make('image')
                ->label(__('admin.article.figure.image'))
                ->image()
                ->disk(PublicMedia::DISK)
                ->directory('articles/inline')
                ->maxSize(5120)
                ->requiredWithout('src'),
            TextInput::make('src')->hidden(),
            TextInput::make('alt')->label(__('admin.article.figure.alt'))->maxLength(255),
            TextInput::make('caption')->label(__('admin.article.figure.caption'))->maxLength(500),
        ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function toPreviewHtml(array $config): string
    {
        return FigureBlockConverter::figureHtml($config);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        return FigureBlockConverter::figureHtml($config);
    }
}
