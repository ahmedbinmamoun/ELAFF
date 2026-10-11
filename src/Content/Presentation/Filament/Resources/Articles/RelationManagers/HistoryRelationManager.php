<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Articles\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

/**
 * FR-ART-08: the audit log of the article, read-only.
 */
final class HistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return (string) __('admin.history.title');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(static fn (Builder $query) => $query->with('causer'))
            ->columns([
                TextColumn::make('event')
                    ->label(__('admin.history.event'))
                    ->badge()
                    ->formatStateUsing(static fn (?string $state): string => (string) __('admin.history.events.'.$state)),
                TextColumn::make('causer.name')->label(__('admin.history.user')),
                TextColumn::make('details')
                    ->label(__('admin.history.details'))
                    ->state(static fn (Activity $record): string => self::details($record))
                    ->wrap(),
                TextColumn::make('created_at')->label(__('admin.history.created_at'))->dateTime('Y-m-d H:i'),
            ]);
    }

    private static function details(Activity $activity): string
    {
        $properties = $activity->properties?->all() ?? [];
        $changes = $properties['changes'] ?? null;

        if (is_array($changes)) {
            $fields = array_map(static fn (int|string $field): string => (string) __('admin.article.fields.'.str_replace(['_ids', '_id'], ['s', ''], (string) $field)), array_keys($changes));

            return (string) __('admin.history.changed_fields', ['fields' => implode('، ', $fields)]);
        }

        return implode(' · ', array_filter(array_map(
            static fn (mixed $v): string => is_scalar($v) ? (string) $v : '',
            $properties,
        )));
    }
}
