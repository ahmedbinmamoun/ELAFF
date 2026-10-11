<?php

declare(strict_types=1);

use Database\Seeders\CategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Content\Application\Article\MarkArticleBreaking\MarkArticleBreakingCommand;
use Elaf\Content\Application\Article\MarkArticleBreaking\MarkArticleBreakingHandler;
use Elaf\Content\Application\Article\PublishArticle\PublishArticleCommand;
use Elaf\Content\Application\Article\PublishArticle\PublishArticleHandler;
use Elaf\Content\Application\Article\ScheduleArticle\ScheduleArticleCommand;
use Elaf\Content\Application\Article\ScheduleArticle\ScheduleArticleHandler;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Contributors\Domain\ContributorStatus;
use Elaf\Contributors\Presentation\Filament\Resources\MyArticles\Pages\CreateMyArticle;
use Elaf\Contributors\Presentation\Filament\Resources\MyArticles\Pages\EditMyArticle;
use Elaf\Contributors\Presentation\Filament\Resources\MyArticles\Pages\ListMyArticles;
use Elaf\Contributors\Presentation\Filament\Resources\MyArticles\Pages\ViewMyArticle;
use Elaf\Identity\Application\Role;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\AdminFixtures;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, CategorySeeder::class]);
    Filament::setCurrentPanel('writer');
    $this->contributor = AdminFixtures::contributor();
    $this->open = CategoryModel::query()->where('is_open_to_contributors', true)->value('id');
});

it('lets each account state in or out of the portal, and never into /admin (FR-CON-03, 12; PRD §16)', function (ContributorStatus $status, int $writer): void {
    $this->actingAs(AdminFixtures::contributor($status));

    $this->get('/writer')->assertStatus($writer);
    $this->get('/admin')->assertForbidden();
})->with([
    'pending email' => [ContributorStatus::PendingEmail, 403],
    'pending approval' => [ContributorStatus::PendingApproval, 200],
    'active' => [ContributorStatus::Active, 200],
    'rejected' => [ContributorStatus::Rejected, 403],
    'suspended' => [ContributorStatus::Suspended, 403],
]);

it('shows pending contributors their status only', function (): void {
    $this->actingAs(AdminFixtures::contributor(ContributorStatus::PendingApproval));

    $this->get('/writer')->assertOk()->assertSee(__('contributors.dashboard.pending_title'));
    $this->get('/writer/articles')->assertForbidden();
});

it('keeps staff out of the contributor portal', function (): void {
    $this->actingAs(AdminFixtures::user(Role::Editor))->get('/writer')->assertForbidden();
});

it('writes an article signed with the contributor\'s own byline, in an open category only (FR-CON-04, 06)', function (): void {
    $this->actingAs($this->contributor);

    Livewire::test(CreateMyArticle::class)
        ->fillForm(['title' => 'رأي في السوق', 'body' => '<p class="x">نص <a href="https://example.com" target="_blank">مصدر</a></p>', 'category_id' => $this->open, 'tags' => ['اقتصاد']])
        ->call('create')
        ->assertHasNoFormErrors();

    $article = ArticleModel::query()->with('authors')->sole();
    expect($article->origin)->toBe('contributor')
        ->and($article->status)->toBe('draft')
        ->and($article->created_by)->toBe($this->contributor->id)
        ->and($article->authors->pluck('user_id')->all())->toBe([$this->contributor->id])
        ->and($article->body)->toBe('<p>نص <a href="https://example.com" rel="nofollow">مصدر</a></p>');

    $closed = CategoryModel::query()->where('is_open_to_contributors', false)->value('id');
    Livewire::test(CreateMyArticle::class)
        ->fillForm(['title' => 'محاولة', 'body' => '<p>نص</p>', 'category_id' => $closed])
        ->call('create')
        ->assertHasFormErrors(['category_id']);
});

it('only ever shows the contributor their own articles (FR-CON-05)', function (): void {
    $mine = AdminFixtures::contributorArticle(ArticleStatus::Draft, $this->contributor, 'مقالي');
    $theirs = AdminFixtures::contributorArticle(ArticleStatus::Draft, AdminFixtures::contributor(name: 'ضيف آخر'), 'مقال غيري');
    $staff = AdminFixtures::article(ArticleStatus::Published, AdminFixtures::user(Role::Writer));
    $this->actingAs($this->contributor);

    Livewire::test(ListMyArticles::class)->assertCanSeeTableRecords([$mine])->assertCanNotSeeTableRecords([$theirs, $staff]);
    $this->get("/writer/articles/{$theirs->id}")->assertNotFound();
    $this->get("/writer/articles/{$staff->id}/edit")->assertNotFound();
});

it('submits, withdraws, and is locked out of editing while in review (FR-CON-06, 07)', function (): void {
    $article = AdminFixtures::contributorArticle(ArticleStatus::Draft, $this->contributor);
    $this->actingAs($this->contributor);

    Livewire::test(ListMyArticles::class)->callAction(TestAction::make('submit')->table($article));
    expect($article->refresh()->status)->toBe('in_review');

    $this->get("/writer/articles/{$article->id}/edit")->assertForbidden();
    $this->get("/writer/articles/{$article->id}")->assertOk();

    Livewire::test(ViewMyArticle::class, ['record' => $article->getRouteKey()])->callAction('withdraw');
    expect($article->refresh()->status)->toBe('draft');
});

it('shows the editor\'s note on a returned article and lets the contributor fix and resubmit', function (): void {
    $article = AdminFixtures::contributorArticle(ArticleStatus::Returned, $this->contributor);
    $this->actingAs($this->contributor);

    $this->get("/writer/articles/{$article->id}/edit")->assertOk()->assertSee('أضف مصادر للأرقام');

    Livewire::test(EditMyArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm(['body' => '<p>نص مع مصادر</p>'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->callAction('submit');

    expect($article->refresh()->status)->toBe('in_review')->and($article->body)->toBe('<p>نص مع مصادر</p>');
});

it('can never publish, schedule or mark breaking — not in the UI, not through the use cases', function (ArticleStatus $status): void {
    $article = AdminFixtures::contributorArticle($status, $this->contributor);
    $this->actingAs($this->contributor);

    Livewire::test(ViewMyArticle::class, ['record' => $article->getRouteKey()])
        ->assertActionDoesNotExist('publish')
        ->assertActionDoesNotExist('schedule')
        ->assertActionDoesNotExist('mark_breaking');

    $id = $this->contributor->id;
    expect(fn () => app(PublishArticleHandler::class)->handle(new PublishArticleCommand($id, $article->id)))->toThrow(NotAuthorized::class)
        ->and(fn () => app(ScheduleArticleHandler::class)->handle(new ScheduleArticleCommand($id, $article->id, now()->addDay()->toDateTimeImmutable())))->toThrow(NotAuthorized::class)
        ->and(fn () => app(MarkArticleBreakingHandler::class)->handle(new MarkArticleBreakingCommand($id, $article->id)))->toThrow(NotAuthorized::class);

    expect($article->refresh()->status)->toBe($status->value)->and($article->is_breaking)->toBeFalse();
})->with([ArticleStatus::Approved, ArticleStatus::Draft]);

it('cannot edit a published article (FR-CON-09)', function (): void {
    $article = AdminFixtures::contributorArticle(ArticleStatus::Published, $this->contributor);
    $this->actingAs($this->contributor);

    $this->get("/writer/articles/{$article->id}/edit")->assertForbidden();
});

it('requests a name change from My Profile; bio changes at once', function (): void {
    $this->actingAs($this->contributor);

    Livewire::test(Elaf\Contributors\Presentation\Filament\Auth\MyProfile::class)
        ->fillForm(['name' => 'اسم جديد', 'bio' => 'نبذة محدثة'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->contributor->refresh()->name)->toBe('كاتب ضيف');
    $profile = Elaf\Contributors\Infrastructure\Persistence\ContributorProfileModel::query()->where('user_id', $this->contributor->id)->sole();
    expect($profile->requested_name)->toBe('اسم جديد')->and($profile->bio)->toBe('نبذة محدثة');
});
