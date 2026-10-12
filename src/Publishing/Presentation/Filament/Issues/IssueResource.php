<?php

declare(strict_types=1);

namespace Elaf\Publishing\Presentation\Filament\Issues;

use BackedEnum;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Publishing\Application\Delete\DeleteIssueCommand;
use Elaf\Publishing\Application\Delete\DeleteIssueHandler;
use Elaf\Publishing\Application\Issue\IssueInput;
use Elaf\Publishing\Application\Retry\RetryIssueProcessingCommand;
use Elaf\Publishing\Application\Retry\RetryIssueProcessingHandler;
use Elaf\Publishing\Domain\Issue\IssueFile;
use Elaf\Publishing\Domain\Issue\ProcessingStatus;
use Elaf\Publishing\Infrastructure\Persistence\IssueModel;
use Elaf\Publishing\Presentation\Filament\Issues\Pages\CreateIssue;
use Elaf\Publishing\Presentation\Filament\Issues\Pages\EditIssue;
use Elaf\Publishing\Presentation\Filament\Issues\Pages\ListIssues;
use Elaf\Publishing\Presentation\Filament\Issues\Pages\ViewIssue;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseDeleteAction;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Elaf\Shared\Presentation\PublicMedia;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * FR-ISS-01, 06: upload, correct, reprocess and delete print issues. Every
 * change goes through a Publishing use case.
 *
 * @extends resource<IssueModel>
 */
final class IssueResource extends Resource
{
    protected static ?string $model = IssueModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?int $navigationSort = 15;

    protected static ?string $recordTitleAttribute = 'number';

    public static function getModelLabel(): string
    {
        return (string) __('admin.issue.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.issue.plural');
    }

    public static function getNavigationGroup(): string
    {
        return (string) __('admin.navigation.print');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('number')->label(self::label('number'))->integer()->minValue(1)->required()
                    ->default(static fn (): int => self::nextNumber()),
                DatePicker::make('published_on')->label(self::label('published_on'))->required()->native(false)
                    ->default(static fn (): string => now()->format('Y-m-d')),
                TextInput::make('title')->label(self::label('title'))->maxLength(200)->columnSpanFull(),
                FileUpload::make('pdf')
                    ->label(self::label('pdf'))
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(intdiv(IssueFile::MAX_BYTES, 1024))
                    ->storeFiles(false)
                    ->required(static fn (string $operation): bool => $operation === 'create')
                    ->helperText(static fn (string $operation): string => (string) __($operation === 'create' ? 'admin.issue.fields.pdf_hint' : 'admin.issue.fields.pdf_replace_hint'))
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                ImageEntry::make('cover_thumb_path')->label(self::label('cover'))->disk(PublicMedia::DISK)->imageHeight(220)->placeholder('—'),
                TextEntry::make('number')->label(self::label('number')),
                TextEntry::make('published_on')->label(self::label('published_on'))->date('Y-m-d'),
                TextEntry::make('title')->label(self::label('title'))->placeholder('—'),
                TextEntry::make('processing_status')->label(self::label('status'))->badge()
                    ->formatStateUsing(static fn (string $state): string => self::statusLabel($state))
                    ->color(static fn (string $state): string => self::statusColor($state)),
                TextEntry::make('processing_error')->label(self::label('error'))->placeholder('—')
                    ->formatStateUsing(static fn (?string $state): string => self::errorLabel($state))
                    ->visible(static fn (IssueModel $record): bool => $record->processing_status === ProcessingStatus::Failed->value),
                TextEntry::make('page_count')->label(self::label('page_count'))->placeholder('—'),
                TextEntry::make('pdf_size')->label(self::label('pdf_size'))->formatStateUsing(static fn (int $state): string => (string) Number::fileSize($state, 1)),
                TextEntry::make('downloads_count')->label(self::label('downloads'))->numeric(),
                TextEntry::make('processed_at')->label(self::label('processed_at'))->dateTime('Y-m-d H:i')->placeholder('—'),
            ]),
            Section::make(self::label('articles'))->schema([
                TextEntry::make('linked_articles')
                    ->hiddenLabel()
                    ->state(static fn (IssueModel $record): array => self::linkedArticles($record))
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->placeholder(__('admin.issue.no_articles')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('number', 'desc')
            ->poll('10s')
            ->columns([
                ImageColumn::make('cover_thumb_path')->label(self::label('cover'))->disk(PublicMedia::DISK)->imageHeight(64),
                TextColumn::make('number')->label(self::label('number'))->sortable()->searchable(),
                TextColumn::make('published_on')->label(self::label('published_on'))->date('Y-m-d')->sortable(),
                TextColumn::make('title')->label(self::label('title'))->limit(40)->toggleable()->placeholder('—'),
                TextColumn::make('page_count')->label(self::label('page_count'))->placeholder('—'),
                TextColumn::make('processing_status')->label(self::label('status'))->badge()
                    ->formatStateUsing(static fn (string $state): string => self::statusLabel($state))
                    ->color(static fn (string $state): string => self::statusColor($state)),
                TextColumn::make('downloads_count')->label(self::label('downloads'))->numeric()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('processing_status')->label(self::label('status'))
                    ->options(array_combine(
                        array_map(static fn (ProcessingStatus $s): string => $s->value, ProcessingStatus::cases()),
                        array_map(static fn (ProcessingStatus $s): string => self::statusLabel($s->value), ProcessingStatus::cases()),
                    )),
                SelectFilter::make('year')->label(self::label('year'))
                    ->options(static fn (): array => self::years())
                    ->query(static fn (Builder $query, array $data): Builder => is_numeric($data['value'] ?? null)
                        ? $query->whereYear('published_on', (int) $data['value'])
                        : $query),
            ])
            ->recordActions([ViewAction::make(), self::retryAction(), EditAction::make(), self::deleteAction()]);
    }

    public static function retryAction(): Action
    {
        return Action::make('retry')
            ->label(__('admin.issue.retry'))
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->requiresConfirmation()
            ->visible(static fn (mixed $record): bool => $record instanceof IssueModel && $record->processing_status === ProcessingStatus::Failed->value)
            ->action(static function (IssueModel $record, Action $action): void {
                $actor = Auth::id();
                $ok = is_int($actor) && UseCaseErrors::forAction(static fn () => app(RetryIssueProcessingHandler::class)
                    ->handle(new RetryIssueProcessingCommand($actor, $record->id)));

                if (! $ok) {
                    $action->halt();
                }

                Notification::make()->success()->title(__('admin.issue.retry_queued'))->send();
            });
    }

    public static function deleteAction(): DeleteAction
    {
        return UseCaseDeleteAction::make(static fn (Model $record) => app(DeleteIssueHandler::class)
            ->handle(new DeleteIssueCommand(FormData::actorId(), FormData::key($record))));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function input(array $data): IssueInput
    {
        return new IssueInput(
            number: FormData::int($data, 'number') ?? 0,
            publishedOn: substr(FormData::string($data, 'published_on'), 0, 10),
            title: FormData::text($data, 'title'),
        );
    }

    /**
     * The local path of the PDF picked in the form (kept as a Livewire
     * temporary upload: the use case stores it).
     *
     * @param  array<string, mixed>  $data
     */
    public static function uploadedPdf(array $data): ?string
    {
        $value = $data['pdf'] ?? null;
        $file = is_array($value) ? (array_values($value)[0] ?? null) : $value;

        return $file instanceof TemporaryUploadedFile ? $file->getRealPath() : null;
    }

    public static function statusLabel(string $status): string
    {
        return (string) __('admin.issue.status.'.$status);
    }

    public static function errorLabel(?string $reason): string
    {
        $key = 'admin.issue.errors.'.($reason ?? 'unknown');
        $label = __($key);

        return is_string($label) && $label !== $key ? $label : (string) __('admin.issue.errors.unknown');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIssues::route('/'),
            'create' => CreateIssue::route('/create'),
            'view' => ViewIssue::route('/{record}'),
            'edit' => EditIssue::route('/{record}/edit'),
        ];
    }

    private static function statusColor(string $status): string
    {
        return match (ProcessingStatus::tryFrom($status)) {
            ProcessingStatus::Ready => 'success',
            ProcessingStatus::Failed => 'danger',
            ProcessingStatus::Processing => 'info',
            default => 'gray',
        };
    }

    private static function nextNumber(): int
    {
        $max = IssueModel::query()->max('number');

        return (is_numeric($max) ? (int) $max : 0) + 1;
    }

    /**
     * @return array<int, string>
     */
    private static function years(): array
    {
        $years = IssueModel::query()->orderByDesc('published_on')->get(['published_on'])
            ->map(static fn (IssueModel $issue): int => (int) $issue->published_on->format('Y'))
            ->unique()
            ->values()
            ->all();

        return array_combine($years, array_map(strval(...), $years));
    }

    /**
     * Published or not: editors see every linked article.
     *
     * @return list<string>
     */
    private static function linkedArticles(IssueModel $issue): array
    {
        return array_values(ArticleModel::query()
            ->where('issue_id', $issue->id)
            ->orderBy('issue_page')
            ->orderBy('title')
            ->get(['issue_page', 'title'])
            ->map(static fn (ArticleModel $a): string => (string) __('admin.issue.article_entry', ['page' => $a->issue_page, 'title' => $a->title]))
            ->all());
    }

    private static function label(string $field): string
    {
        return (string) __('admin.issue.fields.'.$field);
    }
}
