<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Content\Application\Article\ApproveArticle\ApproveArticleCommand;
use Elaf\Content\Application\Article\ApproveArticle\ApproveArticleHandler;
use Elaf\Content\Application\Article\ArticleInput;
use Elaf\Content\Application\Article\CreateArticleDraft\CreateArticleDraftCommand;
use Elaf\Content\Application\Article\CreateArticleDraft\CreateArticleDraftHandler;
use Elaf\Content\Application\Article\PublishArticle\PublishArticleCommand;
use Elaf\Content\Application\Article\PublishArticle\PublishArticleHandler;
use Elaf\Content\Application\Article\SubmitArticleForReview\SubmitArticleForReviewCommand;
use Elaf\Content\Application\Article\SubmitArticleForReview\SubmitArticleForReviewHandler;
use Elaf\Content\Application\Article\UpdateArticle\UpdateArticleCommand;
use Elaf\Content\Application\Article\UpdateArticle\UpdateArticleHandler;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Infrastructure\Audit\RecordArticleActivity;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Identity\Application\Role;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, CategorySeeder::class]);
    $this->user = static fn (Role $role): User => User::factory()->create()->assignRole($role->value);
    $this->input = static fn (string $title): ArticleInput => new ArticleInput(
        title: $title,
        body: '<p>نص</p><script>x</script>',
        sourceName: 'إيلاف',
        categoryId: CategoryModel::query()->value('id'),
        authorIds: [AuthorModel::query()->create(['name' => 'كاتب', 'slug' => 'katib-'.uniqid()])->id],
        tagNames: ['اقتصاد'],
    );
});

it('runs the whole workflow through the real container and logs every step with actor, time and diff (FR-ART-08)', function (): void {
    $writer = ($this->user)(Role::Writer);
    $editor = ($this->user)(Role::Editor);
    $chief = ($this->user)(Role::EditorInChief);

    $this->actingAs($writer);
    $id = app(CreateArticleDraftHandler::class)->handle(new CreateArticleDraftCommand($writer->id, ArticleOrigin::Staff, ($this->input)('الأصل')));
    app(UpdateArticleHandler::class)->handle(new UpdateArticleCommand($writer->id, $id->value, ($this->input)('المعدل')));
    app(SubmitArticleForReviewHandler::class)->handle(new SubmitArticleForReviewCommand($writer->id, $id->value));

    $this->actingAs($editor);
    app(ApproveArticleHandler::class)->handle(new ApproveArticleCommand($editor->id, $id->value));

    $this->actingAs($chief);
    app(PublishArticleHandler::class)->handle(new PublishArticleCommand($chief->id, $id->value));

    $model = ArticleModel::query()->findOrFail($id->value);
    expect($model->status)->toBe('published')
        ->and($model->body)->toBe('<p>نص</p>')
        ->and($model->reading_minutes)->toBe(1)
        ->and($model->published_at)->not->toBeNull();

    $log = Activity::query()->inLog(RecordArticleActivity::LOG_NAME)->orderBy('id')->get();
    expect($log->pluck('event')->all())->toBe(['created', 'updated', 'submitted_for_review', 'approved', 'published'])
        ->and($log->pluck('causer_id')->map(fn ($v) => (int) $v)->all())->toBe([$writer->id, $writer->id, $writer->id, $editor->id, $chief->id])
        ->and($log->every(fn (Activity $a): bool => $a->subject_type === (new ArticleModel)->getMorphClass() && (int) $a->subject_id === $id->value))->toBeTrue();

    $diff = $log[1]->properties->get('changes');
    expect($diff['title'])->toBe(['الأصل', 'المعدل'])
        ->and(DB::table('article_revisions')->where('article_id', $id->value)->count())->toBe(2);
});

it('enforces the role matrix through the real Gate and Spatie roles', function (): void {
    $writer = ($this->user)(Role::Writer);
    $this->actingAs($writer);
    $id = app(CreateArticleDraftHandler::class)->handle(new CreateArticleDraftCommand($writer->id, ArticleOrigin::Staff, ($this->input)('مقال')));
    app(SubmitArticleForReviewHandler::class)->handle(new SubmitArticleForReviewCommand($writer->id, $id->value));

    $editor = ($this->user)(Role::Editor);
    $this->actingAs($editor);
    app(ApproveArticleHandler::class)->handle(new ApproveArticleCommand($editor->id, $id->value));

    expect(fn () => app(PublishArticleHandler::class)->handle(new PublishArticleCommand($editor->id, $id->value)))
        ->toThrow(NotAuthorized::class);
    expect(ArticleModel::query()->findOrFail($id->value)->status)->toBe('approved');
});

it('logs nothing when the transaction rolls back', function (): void {
    $writer = ($this->user)(Role::Writer);
    $this->actingAs($writer);
    $bad = new ArticleInput(title: 'x', body: '', sourceName: 'إيلاف', categoryId: 999_999);

    expect(fn () => app(CreateArticleDraftHandler::class)->handle(new CreateArticleDraftCommand($writer->id, ArticleOrigin::Staff, $bad)))
        ->toThrow(Elaf\Shared\Application\Validation\InvalidInput::class);
    expect(Activity::query()->count())->toBe(0)
        ->and(ArticleModel::query()->count())->toBe(0);
});
