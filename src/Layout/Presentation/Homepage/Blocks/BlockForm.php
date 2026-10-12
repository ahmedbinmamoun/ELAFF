<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Homepage\Blocks;

use Filament\Forms\Components\TextInput;

/**
 * Admin fields for block options, so each block states its options once.
 */
final class BlockForm
{
    public static function number(string $key, int $default, int $min, int $max): TextInput
    {
        return TextInput::make('options.'.$key)
            ->label(__('admin.homepage.options.'.$key))
            ->integer()
            ->minValue($min)
            ->maxValue($max)
            ->placeholder((string) $default)
            ->helperText(__('admin.homepage.range', ['min' => $min, 'max' => $max, 'default' => $default]));
    }

    public static function label(string $type): string
    {
        return (string) __('admin.homepage.types.'.$type);
    }
}
