<?php

declare(strict_types=1);

use Database\Seeders\CategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Presentation\Filament\Resources\Articles\Pages\ViewArticle;
use Elaf\Contributors\Domain\ContributorStatus;
use Elaf\Contributors\Infrastructure\Mail\ElafMail;
use Elaf\Contributors\Infrastructure\Persistence\ContributorProfileModel;
use Elaf\Contributors\Presentation\Filament\Admin\Contributors\Pages\ListContributors;
use Elaf\Identity\Application\Role;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Support\AdminFixtures;
use Tests\Support\SettingsFixtures;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, CategorySeeder::class]);
    Filament::setCurrentPanel('admin');
    Mail::fake();
    $this->editor = AdminFixtures::user(Role::Editor);
    $this->profile = static fn ($user): ContributorProfileModel => ContributorProfileModel::query()->where('user_id', $user->id)->sole();
});

it('lets an editor approve a pending contributor: author linked, role granted, email sent (FR-CON-03, 04, 11)', function (): void {
    $pending = AdminFixtures::contributor(ContributorStatus::PendingApproval, 'آمنة الطيب');
    $this->actingAs($this->editor);

    Livewire::test(ListContributors::class)->callAction(TestAction::make('approve')->table(($this->profile)($pending)));

    expect($pending->refresh()->status)->toBe('active')
        ->and($pending->hasRole('contributor'))->toBeTrue()
        ->and(AuthorModel::query()->where('user_id', $pending->id)->value('name'))->toBe('آمنة الطيب');
    Mail::assertQueued(ElafMail::class, static fn (ElafMail $m): bool => $m->hasTo($pending->email) && $m->subjectLine === __('mail.account_approved.subject'));
});

it('rejects with a reason that is mailed to the applicant', function (): void {
    $pending = AdminFixtures::contributor(ContributorStatus::PendingApproval);
    $this->actingAs($this->editor);

    Livewire::test(ListContributors::class)
        ->callAction(TestAction::make('reject')->table(($this->profile)($pending)), data: ['reason' => ''])
        ->assertHasActionErrors(['reason']);
    Livewire::test(ListContributors::class)
        ->callAction(TestAction::make('reject')->table(($this->profile)($pending)), data: ['reason' => 'المقالات المرسلة غير أصلية']);

    expect($pending->refresh()->status)->toBe('rejected');
    Mail::assertQueued(ElafMail::class, static fn (ElafMail $m): bool => $m->hasTo($pending->email) && in_array('المقالات المرسلة غير أصلية', $m->lines, true));
});

it('keeps writers away from contributor moderation', function (): void {
    $this->actingAs(AdminFixtures::user(Role::Writer))->get('/admin/contributors')->assertForbidden();
});

it('lets an editor approve or return a contributor article, mailing the contributor (FR-CON-07, 11)', function (): void {
    $contributor = AdminFixtures::contributor();
    $returned = AdminFixtures::contributorArticle(ArticleStatus::InReview, $contributor, 'مقال سيعاد');
    $approved = AdminFixtures::contributorArticle(ArticleStatus::InReview, $contributor, 'مقال سيعتمد');
    $this->actingAs($this->editor);

    Livewire::test(ViewArticle::class, ['record' => $returned->getRouteKey()])->callAction('return', data: ['note' => 'أضف مصدراً']);
    Livewire::test(ViewArticle::class, ['record' => $approved->getRouteKey()])->callAction('approve');

    expect($returned->refresh()->status)->toBe('returned')->and($approved->refresh()->status)->toBe('approved');
    Mail::assertQueued(ElafMail::class, static fn (ElafMail $m): bool => $m->hasTo($contributor->email) && in_array('أضف مصدراً', $m->lines, true));
    Mail::assertQueued(ElafMail::class, static fn (ElafMail $m): bool => $m->hasTo($contributor->email) && $m->subjectLine === __('mail.article_approved.subject', ['title' => 'مقال سيعتمد']));
});

it('leaves publishing to the Editor-in-Chief unless editors are allowed (FR-CON-08)', function (): void {
    $contributor = AdminFixtures::contributor();
    $article = AdminFixtures::contributorArticle(ArticleStatus::Approved, $contributor);
    $staffArticle = AdminFixtures::article(ArticleStatus::Approved, AdminFixtures::user(Role::Writer));
    $view = static fn ($a) => Livewire::test(ViewArticle::class, ['record' => $a->getRouteKey()]);

    $this->actingAs($this->editor);
    $view($article)->assertActionHidden('publish')->assertActionHidden('schedule');

    SettingsFixtures::set(['contributors_editor_can_publish' => true]);
    $view($staffArticle)->assertActionHidden('publish');
    $view($article)->assertActionVisible('publish')->assertActionVisible('schedule')->callAction('publish');
    expect($article->refresh()->status)->toBe('published');
    Mail::assertQueued(ElafMail::class, static fn (ElafMail $m): bool => $m->hasTo($contributor->email) && $m->subjectLine === __('mail.article_published.subject', ['title' => $article->title]));

    SettingsFixtures::set(['contributors_editor_can_publish' => false]);
    $other = AdminFixtures::contributorArticle(ArticleStatus::Approved, $contributor, 'آخر');
    $this->actingAs(AdminFixtures::user(Role::EditorInChief));
    $view($other)->callAction('publish');
    expect($other->refresh()->status)->toBe('published');
});

it('notifies reviewers when a contributor submits', function (): void {
    $contributor = AdminFixtures::contributor();
    $article = AdminFixtures::contributorArticle(ArticleStatus::Draft, $contributor, 'مقال جديد');
    Filament::setCurrentPanel('writer');
    $this->actingAs($contributor);

    Livewire::test(Elaf\Contributors\Presentation\Filament\Resources\MyArticles\Pages\ListMyArticles::class)
        ->callAction(TestAction::make('submit')->table($article));

    Mail::assertQueued(ElafMail::class, fn (ElafMail $m): bool => $m->hasTo($this->editor->email)
        && $m->subjectLine === __('mail.submission.subject', ['title' => 'مقال جديد']));
});

it('suspends a contributor: sign-in blocked, published articles stay until unpublished in bulk (FR-CON-12)', function (): void {
    $contributor = AdminFixtures::contributor();
    $published = AdminFixtures::contributorArticle(ArticleStatus::Published, $contributor, 'منشور');
    $this->actingAs($this->editor);

    Livewire::test(ListContributors::class)->callAction(TestAction::make('suspend')->table(($this->profile)($contributor)));
    expect($contributor->refresh()->status)->toBe('suspended')
        ->and($contributor->hasRole('contributor'))->toBeFalse()
        ->and($published->refresh()->status)->toBe('published');

    $this->actingAs($contributor)->get('/writer')->assertForbidden();

    Filament::setCurrentPanel('admin');
    $this->actingAs($this->editor);
    Livewire::test(ListContributors::class)->callAction(TestAction::make('unpublish_all')->table(($this->profile)($contributor)));
    expect($published->refresh()->status)->toBe('unpublished');

    Livewire::test(ListContributors::class)->callAction(TestAction::make('reinstate')->table(($this->profile)($contributor)));
    expect($contributor->refresh()->status)->toBe('active')->and($contributor->hasRole('contributor'))->toBeTrue();
});

it('approves a requested display name, renaming the byline', function (): void {
    $contributor = AdminFixtures::contributor(name: 'الاسم القديم');
    ContributorProfileModel::query()->where('user_id', $contributor->id)->update(['requested_name' => 'الاسم الجديد']);
    $this->actingAs($this->editor);

    Livewire::test(ListContributors::class)->callAction(TestAction::make('approve_name')->table(($this->profile)($contributor)));

    expect($contributor->refresh()->name)->toBe('الاسم الجديد')
        ->and(AuthorModel::query()->where('user_id', $contributor->id)->value('name'))->toBe('الاسم الجديد');
});
