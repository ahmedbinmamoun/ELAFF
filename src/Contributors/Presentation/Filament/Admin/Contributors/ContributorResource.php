<?php

declare(strict_types=1);

namespace Elaf\Contributors\Presentation\Filament\Admin\Contributors;

use BackedEnum;
use Closure;
use Elaf\Contributors\Application\Approve\ApproveContributorCommand;
use Elaf\Contributors\Application\Approve\ApproveContributorHandler;
use Elaf\Contributors\Application\ApproveNameChange\ApproveNameChangeCommand;
use Elaf\Contributors\Application\ApproveNameChange\ApproveNameChangeHandler;
use Elaf\Contributors\Application\Reinstate\ReinstateContributorCommand;
use Elaf\Contributors\Application\Reinstate\ReinstateContributorHandler;
use Elaf\Contributors\Application\Reject\RejectContributorCommand;
use Elaf\Contributors\Application\Reject\RejectContributorHandler;
use Elaf\Contributors\Application\Suspend\SuspendContributorCommand;
use Elaf\Contributors\Application\Suspend\SuspendContributorHandler;
use Elaf\Contributors\Application\UnpublishAll\UnpublishAllByContributorCommand;
use Elaf\Contributors\Application\UnpublishAll\UnpublishAllByContributorHandler;
use Elaf\Contributors\Domain\ContributorStatus;
use Elaf\Contributors\Infrastructure\Persistence\ContributorProfileModel;
use Elaf\Contributors\Presentation\Filament\Admin\Contributors\Pages\ListContributors;
use Elaf\Contributors\Presentation\Filament\Admin\Contributors\Pages\ViewContributor;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Elaf\Shared\Presentation\PublicMedia;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Contributor moderation for editors (FR-CON-03, 04, 12).
 *
 * @extends resource<ContributorProfileModel>
 */
final class ContributorResource extends Resource
{
    protected static ?string $model = ContributorProfileModel::class;

    protected static ?string $slug = 'contributors';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?int $navigationSort = 15;

    public static function getModelLabel(): string
    {
        return (string) __('contributors.admin.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('contributors.admin.plural');
    }

    public static function getNavigationGroup(): string
    {
        return (string) __('admin.navigation.content');
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = ContributorProfileModel::query()
            ->whereHas('user', static fn (Builder $q) => $q->where('status', ContributorStatus::PendingApproval->value))
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    /**
     * @return Builder<ContributorProfileModel>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'author']);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextEntry::make('user.name')->label(__('contributors.fields.name')),
                TextEntry::make('user.email')->label(__('contributors.fields.email')),
                TextEntry::make('user.status')->label(__('contributors.admin.status'))->badge()
                    ->formatStateUsing(static fn (string $state): string => (string) __('contributors.status.'.$state)),
                TextEntry::make('requested_name')->label(__('contributors.admin.requested_name'))->placeholder('—'),
                TextEntry::make('phone')->label(__('contributors.fields.phone'))->placeholder('—'),
                TextEntry::make('terms_accepted_at')->label(__('contributors.admin.terms_accepted_at'))->dateTime('Y-m-d H:i'),
                TextEntry::make('bio')->label(__('contributors.fields.bio'))->columnSpanFull(),
                TextEntry::make('rejection_reason')->label(__('contributors.admin.rejection_reason'))->placeholder('—')->columnSpanFull(),
                ImageEntry::make('photo_path')->label(__('contributors.fields.photo'))->disk(PublicMedia::DISK)->circular(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->label(__('contributors.fields.name'))->searchable(),
                TextColumn::make('user.email')->label(__('contributors.fields.email'))->searchable(),
                TextColumn::make('user.status')
                    ->label(__('contributors.admin.status'))
                    ->badge()
                    ->formatStateUsing(static fn (string $state): string => (string) __('contributors.status.'.$state))
                    ->color(static fn (string $state): string => match (ContributorStatus::from($state)) {
                        ContributorStatus::PendingApproval => 'warning',
                        ContributorStatus::Active => 'success',
                        ContributorStatus::Suspended, ContributorStatus::Rejected => 'danger',
                        ContributorStatus::PendingEmail => 'gray',
                    }),
                TextColumn::make('requested_name')->label(__('contributors.admin.requested_name'))->placeholder('—'),
                TextColumn::make('created_at')->label(__('contributors.admin.registered_at'))->date('Y-m-d'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('contributors.admin.status'))
                    ->options(collect(ContributorStatus::cases())->mapWithKeys(static fn (ContributorStatus $s): array => [$s->value => (string) __('contributors.status.'.$s->value)])->all())
                    ->query(static fn (Builder $query, array $data): Builder => FormData::text($data, 'value') === null
                        ? $query
                        : $query->whereHas('user', static fn (Builder $q) => $q->where('status', $data['value']))),
            ])
            ->recordActions([ViewAction::make(), ActionGroup::make(self::actions())]);
    }

    /**
     * @return list<Action>
     */
    public static function actions(): array
    {
        return [
            self::action('approve', Heroicon::OutlinedCheckBadge, 'success', ContributorStatus::Active, ContributorStatus::PendingApproval,
                static fn (int $actor, int $id) => app(ApproveContributorHandler::class)->handle(new ApproveContributorCommand($actor, $id)))
                ->requiresConfirmation(),
            self::action('reject', Heroicon::OutlinedXCircle, 'danger', ContributorStatus::Rejected, ContributorStatus::PendingApproval,
                static fn (int $actor, int $id, array $data) => app(RejectContributorHandler::class)->handle(new RejectContributorCommand($actor, $id, FormData::string($data, 'reason'))))
                ->schema([Textarea::make('reason')->label(__('contributors.admin.rejection_reason'))->required()->rows(3)]),
            self::action('suspend', Heroicon::OutlinedNoSymbol, 'danger', ContributorStatus::Suspended, ContributorStatus::Active,
                static fn (int $actor, int $id) => app(SuspendContributorHandler::class)->handle(new SuspendContributorCommand($actor, $id)))
                ->requiresConfirmation(),
            self::action('reinstate', Heroicon::OutlinedArrowPath, 'success', ContributorStatus::Active, ContributorStatus::Suspended,
                static fn (int $actor, int $id) => app(ReinstateContributorHandler::class)->handle(new ReinstateContributorCommand($actor, $id)))
                ->requiresConfirmation(),
            self::action('approve_name', Heroicon::OutlinedIdentification, 'primary', null, null,
                static fn (int $actor, int $id) => app(ApproveNameChangeHandler::class)->handle(new ApproveNameChangeCommand($actor, $id)))
                ->visible(static fn (ContributorProfileModel $record): bool => $record->requested_name !== null)
                ->modalDescription(static fn (ContributorProfileModel $record): string => (string) __('contributors.admin.approve_name_description', [
                    'old' => $record->user->name ?? '',
                    'new' => $record->requested_name ?? '',
                ]))
                ->requiresConfirmation(),
            self::action('unpublish_all', Heroicon::OutlinedEyeSlash, 'danger', null, null,
                static fn (int $actor, int $id) => app(UnpublishAllByContributorHandler::class)->handle(new UnpublishAllByContributorCommand($actor, $id)))
                ->visible(static fn (ContributorProfileModel $record): bool => in_array($record->user?->status, ['active', 'suspended'], true))
                ->modalDescription(__('contributors.admin.unpublish_all_description'))
                ->requiresConfirmation(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContributors::route('/'),
            'view' => ViewContributor::route('/{record}'),
        ];
    }

    /**
     * @param  Closure(int, int, array<array-key, mixed>): mixed  $run  (actor, contributor user id, form data)
     */
    private static function action(string $name, Heroicon $icon, string $color, ?ContributorStatus $target, ?ContributorStatus $from, Closure $run): Action
    {
        return Action::make($name)
            ->label(__('contributors.admin.actions.'.$name))
            ->icon($icon)
            ->color($color)
            ->visible(static fn (ContributorProfileModel $record): bool => $target === null || $record->user?->status === $from?->value)
            ->action(static function (ContributorProfileModel $record, array $data, Action $action) use ($run): void {
                if (! UseCaseErrors::forAction(static fn () => $run(FormData::actorId(), $record->user_id, $data))) {
                    $action->halt();
                }

                Notification::make()->success()->title(__('admin.workflow.done'))->send();
            });
    }
}
