<?php

declare(strict_types=1);

namespace Elaf\Engagement\Presentation\Filament\Letters;

use BackedEnum;
use Closure;
use Elaf\Content\Presentation\Filament\Resources\Articles\ArticleResource;
use Elaf\Engagement\Application\Letter\Approve\ApproveReaderLetterCommand;
use Elaf\Engagement\Application\Letter\Approve\ApproveReaderLetterHandler;
use Elaf\Engagement\Application\Letter\Convert\ConvertLetterToArticleCommand;
use Elaf\Engagement\Application\Letter\Convert\ConvertLetterToArticleHandler;
use Elaf\Engagement\Application\Letter\Reject\RejectReaderLetterCommand;
use Elaf\Engagement\Application\Letter\Reject\RejectReaderLetterHandler;
use Elaf\Engagement\Domain\Letter\LetterStatus;
use Elaf\Engagement\Infrastructure\Persistence\ReaderLetterModel;
use Elaf\Engagement\Presentation\Filament\Letters\Pages\ListReaderLetters;
use Elaf\Engagement\Presentation\Filament\Letters\Pages\ViewReaderLetter;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * FR-LET-02: the moderation queue. Email and phone are shown here only
 * (FR-LET-03).
 *
 * @extends resource<ReaderLetterModel>
 */
final class ReaderLetterResource extends Resource
{
    protected static ?string $model = ReaderLetterModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return (string) __('admin.letter.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.letter.plural');
    }

    public static function getNavigationGroup(): string
    {
        return (string) __('admin.navigation.content');
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = ReaderLetterModel::query()->where('status', LetterStatus::Pending->value)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('name')->label(self::label('name')),
                TextEntry::make('email')->label(self::label('email'))->copyable(),
                TextEntry::make('phone')->label(self::label('phone'))->placeholder('—'),
                TextEntry::make('status')->label(self::label('status'))->badge()
                    ->formatStateUsing(static fn (string $state): string => self::statusLabel($state))
                    ->color(static fn (string $state): string => self::statusColor($state)),
                TextEntry::make('received_at')->label(self::label('received_at'))->dateTime('Y-m-d H:i'),
                TextEntry::make('moderator_note')->label(self::label('moderator_note'))->placeholder('—'),
                TextEntry::make('title')->label(self::label('title'))->columnSpanFull()->weight('bold'),
                TextEntry::make('body')->label(self::label('body'))->columnSpanFull()
                    ->formatStateUsing(static fn (string $state): string => nl2br(e($state)))->html(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('received_at', 'desc')
            ->columns([
                TextColumn::make('title')->label(self::label('title'))->searchable()->limit(60)->weight('bold'),
                TextColumn::make('name')->label(self::label('name'))->searchable(),
                TextColumn::make('status')->label(self::label('status'))->badge()
                    ->formatStateUsing(static fn (string $state): string => self::statusLabel($state))
                    ->color(static fn (string $state): string => self::statusColor($state)),
                TextColumn::make('article_id')->label(self::label('article'))->placeholder('—')
                    ->url(static fn (ReaderLetterModel $record): ?string => $record->article_id === null ? null : ArticleResource::getUrl('edit', ['record' => $record->article_id])),
                TextColumn::make('received_at')->label(self::label('received_at'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(self::label('status'))->default(LetterStatus::Pending->value)
                    ->options(array_combine(
                        array_map(static fn (LetterStatus $s): string => $s->value, LetterStatus::cases()),
                        array_map(static fn (LetterStatus $s): string => self::statusLabel($s->value), LetterStatus::cases()),
                    )),
            ])
            ->recordActions([ViewAction::make()]);
    }

    /**
     * @return list<Action>
     */
    public static function moderationActions(): array
    {
        return [
            self::action('approve', Heroicon::OutlinedCheckCircle, 'success',
                static fn (ReaderLetterModel $l): bool => $l->status === LetterStatus::Pending->value,
                static fn (ReaderLetterModel $l, array $d, int $actor) => app(ApproveReaderLetterHandler::class)->handle(new ApproveReaderLetterCommand($actor, $l->id)),
            )->requiresConfirmation(),
            self::action('reject', Heroicon::OutlinedXCircle, 'danger',
                static fn (ReaderLetterModel $l): bool => $l->status === LetterStatus::Pending->value,
                static fn (ReaderLetterModel $l, array $d, int $actor) => app(RejectReaderLetterHandler::class)->handle(
                    new RejectReaderLetterCommand($actor, $l->id, is_string($d['reason'] ?? null) ? $d['reason'] : ''),
                ),
            )->schema([Textarea::make('reason')->label(self::label('reason'))->required()->rows(3)]),
            self::action('convert', Heroicon::OutlinedDocumentPlus, 'primary',
                static fn (ReaderLetterModel $l): bool => $l->status === LetterStatus::Approved->value && $l->article_id === null,
                static fn (ReaderLetterModel $l, array $d, int $actor) => app(ConvertLetterToArticleHandler::class)->handle(new ConvertLetterToArticleCommand($actor, $l->id)),
            )->requiresConfirmation()->modalDescription(__('admin.letter.convert_hint')),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReaderLetters::route('/'),
            'view' => ViewReaderLetter::route('/{record}'),
        ];
    }

    /**
     * @param  Closure(ReaderLetterModel): bool  $visible
     * @param  Closure(ReaderLetterModel, array<array-key, mixed>, int): mixed  $run
     */
    private static function action(string $name, Heroicon $icon, string $color, Closure $visible, Closure $run): Action
    {
        return Action::make($name)
            ->label(__('admin.letter.actions.'.$name))
            ->icon($icon)
            ->color($color)
            ->visible(static fn (mixed $record): bool => $record instanceof ReaderLetterModel && $visible($record))
            ->action(static function (ReaderLetterModel $record, array $data, Action $action) use ($run): void {
                $actor = Auth::id();

                if (! is_int($actor) || ! UseCaseErrors::forAction(static fn () => $run($record, $data, $actor))) {
                    $action->halt();
                }

                // The next step (e.g. convert after approve) shows without a reload.
                $record->refresh();
                Notification::make()->success()->title(__('admin.letter.done'))->send();
            });
    }

    private static function statusLabel(string $status): string
    {
        return (string) __('admin.letter.status.'.$status);
    }

    private static function statusColor(string $status): string
    {
        return match (LetterStatus::tryFrom($status)) {
            LetterStatus::Approved => 'success',
            LetterStatus::Rejected => 'danger',
            default => 'warning',
        };
    }

    private static function label(string $field): string
    {
        return (string) __('admin.letter.fields.'.$field);
    }
}
