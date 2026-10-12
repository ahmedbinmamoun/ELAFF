<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Articles;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Elaf\Content\Application\Article\ApproveArticle\ApproveArticleCommand;
use Elaf\Content\Application\Article\ApproveArticle\ApproveArticleHandler;
use Elaf\Content\Application\Article\AssignArticleToIssue\AssignArticleToIssueCommand;
use Elaf\Content\Application\Article\AssignArticleToIssue\AssignArticleToIssueHandler;
use Elaf\Content\Application\Article\MarkArticleBreaking\MarkArticleBreakingCommand;
use Elaf\Content\Application\Article\MarkArticleBreaking\MarkArticleBreakingHandler;
use Elaf\Content\Application\Article\PromoteArticle\PromoteArticleCommand;
use Elaf\Content\Application\Article\PromoteArticle\PromoteArticleHandler;
use Elaf\Content\Application\Article\PublishArticle\PublishArticleCommand;
use Elaf\Content\Application\Article\PublishArticle\PublishArticleHandler;
use Elaf\Content\Application\Article\ReturnArticleToWriter\ReturnArticleToWriterCommand;
use Elaf\Content\Application\Article\ReturnArticleToWriter\ReturnArticleToWriterHandler;
use Elaf\Content\Application\Article\ScheduleArticle\ScheduleArticleCommand;
use Elaf\Content\Application\Article\ScheduleArticle\ScheduleArticleHandler;
use Elaf\Content\Application\Article\SubmitArticleForReview\SubmitArticleForReviewCommand;
use Elaf\Content\Application\Article\SubmitArticleForReview\SubmitArticleForReviewHandler;
use Elaf\Content\Application\Article\UnmarkArticleBreaking\UnmarkArticleBreakingCommand;
use Elaf\Content\Application\Article\UnmarkArticleBreaking\UnmarkArticleBreakingHandler;
use Elaf\Content\Application\Article\UnpublishArticle\UnpublishArticleCommand;
use Elaf\Content\Application\Article\UnpublishArticle\UnpublishArticleHandler;
use Elaf\Content\Application\Article\WithdrawSubmission\WithdrawSubmissionCommand;
use Elaf\Content\Application\Article\WithdrawSubmission\WithdrawSubmissionHandler;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Publishing\Infrastructure\Persistence\IssueModel;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * One admin action per workflow use case (FR-ART-04, 06, 07). An action is
 * shown when the user holds the permission and the domain transition table
 * allows the move; the use case still decides.
 */
final class ArticleWorkflowActions
{
    /**
     * @return list<Action>
     */
    public static function all(): array
    {
        return [
            self::submit(),
            self::withdraw(),
            self::approve(),
            self::returnToWriter(),
            self::publish(),
            self::schedule(),
            self::unpublish(),
            self::markBreaking(),
            self::unmarkBreaking(),
            self::promote(),
            self::assignIssue(),
        ];
    }

    public static function group(): ActionGroup
    {
        return ActionGroup::make(self::all())->label(__('admin.article.sections.workflow'))->button();
    }

    public static function submit(): Action
    {
        return self::make('submit', Heroicon::OutlinedPaperAirplane, 'primary',
            static fn (ArticleModel $a): bool => self::can($a, ArticleStatus::InReview) && Gate::allows('submit', $a),
            static fn (ArticleModel $a, array $d, int $actor) => app(SubmitArticleForReviewHandler::class)->handle(new SubmitArticleForReviewCommand($actor, $a->id)),
        )->requiresConfirmation();
    }

    public static function withdraw(): Action
    {
        return self::make('withdraw', Heroicon::OutlinedArrowUturnRight, 'gray',
            static fn (ArticleModel $a): bool => self::can($a, ArticleStatus::Draft) && Gate::allows('submit', $a),
            static fn (ArticleModel $a, array $d, int $actor) => app(WithdrawSubmissionHandler::class)->handle(new WithdrawSubmissionCommand($actor, $a->id)),
        )->requiresConfirmation();
    }

    /**
     * Hidden on one's own article: the domain refuses self-approval anyway.
     */
    public static function approve(): Action
    {
        return self::make('approve', Heroicon::OutlinedCheckBadge, 'success',
            static fn (ArticleModel $a): bool => self::can($a, ArticleStatus::Approved, ContentPermission::ReviewArticles) && $a->created_by !== Auth::id(),
            static fn (ArticleModel $a, array $d, int $actor) => app(ApproveArticleHandler::class)->handle(new ApproveArticleCommand($actor, $a->id)),
        )->requiresConfirmation();
    }

    public static function returnToWriter(): Action
    {
        return self::make('return', Heroicon::OutlinedArrowUturnLeft, 'warning',
            static fn (ArticleModel $a): bool => self::can($a, ArticleStatus::Returned, ContentPermission::ReviewArticles),
            static fn (ArticleModel $a, array $d, int $actor) => app(ReturnArticleToWriterHandler::class)->handle(
                new ReturnArticleToWriterCommand($actor, $a->id, is_string($d['note'] ?? null) ? $d['note'] : ''),
            ),
        )->schema([
            Textarea::make('note')->label(__('admin.workflow.fields.note'))->required()->rows(4),
        ]);
    }

    public static function publish(): Action
    {
        return self::make('publish', Heroicon::OutlinedGlobeAlt, 'success',
            static fn (ArticleModel $a): bool => self::can($a, ArticleStatus::Published) && Gate::allows('publish', $a),
            static fn (ArticleModel $a, array $d, int $actor) => app(PublishArticleHandler::class)->handle(new PublishArticleCommand($actor, $a->id)),
        )->requiresConfirmation();
    }

    public static function schedule(): Action
    {
        return self::make('schedule', Heroicon::OutlinedClock, 'info',
            static fn (ArticleModel $a): bool => self::can($a, ArticleStatus::Scheduled) && Gate::allows('publish', $a),
            static fn (ArticleModel $a, array $d, int $actor) => app(ScheduleArticleHandler::class)->handle(
                new ScheduleArticleCommand($actor, $a->id, self::moment($d['publish_at'] ?? null) ?? new DateTimeImmutable('@0')),
            ),
        )->schema([
            DateTimePicker::make('publish_at')->label(__('admin.workflow.fields.publish_at'))->required()->seconds(false),
        ]);
    }

    public static function unpublish(): Action
    {
        return self::make('unpublish', Heroicon::OutlinedEyeSlash, 'danger',
            static fn (ArticleModel $a): bool => self::can($a, ArticleStatus::Unpublished, ContentPermission::PublishArticles),
            static fn (ArticleModel $a, array $d, int $actor) => app(UnpublishArticleHandler::class)->handle(new UnpublishArticleCommand($actor, $a->id)),
        )->requiresConfirmation();
    }

    public static function markBreaking(): Action
    {
        return self::make('mark_breaking', Heroicon::OutlinedBolt, 'danger',
            static fn (ArticleModel $a): bool => ! $a->is_breaking && Gate::allows(ContentPermission::PromoteArticles->value),
            static fn (ArticleModel $a, array $d, int $actor) => app(MarkArticleBreakingHandler::class)->handle(
                new MarkArticleBreakingCommand($actor, $a->id, self::moment($d['until'] ?? null)),
            ),
        )->schema([
            DateTimePicker::make('until')->label(__('admin.workflow.fields.breaking_until'))->seconds(false),
        ]);
    }

    public static function unmarkBreaking(): Action
    {
        return self::make('unmark_breaking', Heroicon::OutlinedBoltSlash, 'gray',
            static fn (ArticleModel $a): bool => $a->is_breaking && Gate::allows(ContentPermission::PromoteArticles->value),
            static fn (ArticleModel $a, array $d, int $actor) => app(UnmarkArticleBreakingHandler::class)->handle(new UnmarkArticleBreakingCommand($actor, $a->id)),
        )->requiresConfirmation();
    }

    public static function promote(): Action
    {
        return self::make('promote', Heroicon::OutlinedStar, 'warning',
            static fn (ArticleModel $a): bool => Gate::allows(ContentPermission::PromoteArticles->value),
            static fn (ArticleModel $a, array $d, int $actor) => app(PromoteArticleHandler::class)->handle(new PromoteArticleCommand(
                $actor,
                $a->id,
                (bool) ($d['featured'] ?? false),
                is_numeric($d['homepage_priority'] ?? null) ? (int) $d['homepage_priority'] : 0,
            )),
        )
            ->fillForm(static fn (ArticleModel $record): array => ['featured' => $record->is_featured, 'homepage_priority' => $record->homepage_priority])
            ->schema([
                Toggle::make('featured')->label(__('admin.workflow.fields.featured')),
                TextInput::make('homepage_priority')->label(__('admin.workflow.fields.homepage_priority'))->integer()->minValue(0)->default(0),
            ]);
    }

    /**
     * FR-ART-01: the print issue and page the article appeared on.
     */
    public static function assignIssue(): Action
    {
        return self::make('assign_issue', Heroicon::OutlinedNewspaper, 'gray',
            static fn (ArticleModel $a): bool => Gate::allows(ContentPermission::EditAnyArticle->value),
            static fn (ArticleModel $a, array $d, int $actor) => app(AssignArticleToIssueHandler::class)->handle(new AssignArticleToIssueCommand(
                $actor,
                $a->id,
                is_numeric($d['issue_id'] ?? null) ? (int) $d['issue_id'] : null,
                is_numeric($d['issue_page'] ?? null) ? (int) $d['issue_page'] : null,
            )),
        )
            ->fillForm(static fn (ArticleModel $record): array => ['issue_id' => $record->issue_id, 'issue_page' => $record->issue_page])
            ->schema([
                Select::make('issue_id')
                    ->label(__('admin.workflow.fields.issue'))
                    ->helperText(__('admin.workflow.fields.issue_hint'))
                    ->options(static fn (): array => IssueModel::query()
                        ->orderByDesc('number')
                        ->limit(200)
                        ->get(['id', 'number', 'published_on'])
                        ->mapWithKeys(static fn (IssueModel $issue): array => [$issue->id => (string) __('admin.issue.option', [
                            'number' => $issue->number,
                            'date' => $issue->published_on->format('Y-m-d'),
                        ])])
                        ->all())
                    ->searchable()
                    ->live(),
                TextInput::make('issue_page')
                    ->label(__('admin.workflow.fields.issue_page'))
                    ->integer()
                    ->minValue(1)
                    ->required(static fn (callable $get): bool => filled($get('issue_id'))),
            ]);
    }

    /**
     * @param  Closure(ArticleModel): bool  $visible
     * @param  Closure(ArticleModel, array<array-key, mixed>, int): mixed  $run
     */
    private static function make(string $name, Heroicon $icon, string $color, Closure $visible, Closure $run): Action
    {
        return Action::make($name)
            ->label(__('admin.workflow.'.$name))
            ->icon($icon)
            ->color($color)
            ->visible(static fn (mixed $record): bool => $record instanceof ArticleModel && $visible($record))
            ->action(static function (ArticleModel $record, array $data, Action $action, mixed $livewire) use ($run): void {
                $actor = Auth::id();

                if (! is_int($actor) || ! UseCaseErrors::forAction(static fn () => $run($record, $data, $actor))) {
                    $action->halt();
                }

                Notification::make()->success()->title(__('admin.workflow.done'))->send();
                $record->refresh();

                if ($livewire instanceof EditRecord || $livewire instanceof ViewRecord) {
                    $page = Gate::allows('update', $record) ? 'edit' : 'view';
                    /** @var class-string<\Filament\Resources\Resource> $resource */
                    $resource = $livewire::getResource();
                    $action->redirect($resource::getUrl($page, ['record' => $record]));
                }
            });
    }

    private static function can(ArticleModel $article, ArticleStatus $target, ?ContentPermission $permission = null): bool
    {
        return ArticleStatus::from($article->status)->canTransitionTo($target)
            && ($permission === null || Gate::allows($permission->value));
    }

    private static function moment(mixed $value): ?DateTimeImmutable
    {
        return is_string($value) && $value !== ''
            ? new DateTimeImmutable($value, new DateTimeZone(date_default_timezone_get()))
            : null;
    }
}
