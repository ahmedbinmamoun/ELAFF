<?php

declare(strict_types=1);

use Database\Seeders\CategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Presentation\Filament\Resources\Articles\Pages\ListArticles;
use Elaf\Content\Presentation\Filament\Resources\Articles\Pages\ViewArticle;
use Elaf\Identity\Application\Role;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\AdminFixtures;

uses(RefreshDatabase::class);

const WORKFLOW_ACTIONS = ['submit', 'withdraw', 'approve', 'return', 'publish', 'schedule', 'unpublish', 'mark_breaking', 'unmark_breaking', 'promote'];

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, CategorySeeder::class]);
    Filament::setCurrentPanel('admin');
    $this->writer = AdminFixtures::user(Role::Writer);
});

/*
 * PRD §2 matrix, as workflow buttons: what each role sees on an article
 * written by someone else (the writer sees their own).
 */
$chief = [
    'draft' => ['submit', 'mark_breaking', 'promote'],
    'in_review' => ['withdraw', 'approve', 'return', 'mark_breaking', 'promote'],
    'returned' => ['submit', 'mark_breaking', 'promote'],
    'approved' => ['publish', 'schedule', 'mark_breaking', 'promote'],
    'scheduled' => ['publish', 'schedule', 'mark_breaking', 'promote'],
    'published' => ['unpublish', 'mark_breaking', 'promote'],
    'unpublished' => ['publish', 'mark_breaking', 'promote'],
];
$matrix = [
    'writer (own article)' => ['draft' => ['submit'], 'in_review' => ['withdraw'], 'returned' => ['submit']],
    'editor' => ['draft' => ['submit'], 'in_review' => ['withdraw', 'approve', 'return'], 'returned' => ['submit']],
    'editor-in-chief' => $chief,
    'admin' => $chief,
];
$roles = ['writer (own article)' => null, 'editor' => Role::Editor, 'editor-in-chief' => Role::EditorInChief, 'admin' => Role::Admin];

$cases = [];
foreach ($matrix as $who => $byStatus) {
    foreach (ArticleStatus::cases() as $status) {
        $cases["{$who} / {$status->value}"] = [$roles[$who], $status, $byStatus[$status->value] ?? []];
    }
}

it('shows exactly the workflow actions the role may use', function (?Role $role, ArticleStatus $status, array $expected): void {
    $article = AdminFixtures::article($status, $this->writer);
    $this->actingAs($role === null ? $this->writer : AdminFixtures::user($role));

    $page = Livewire::test(ViewArticle::class, ['record' => $article->getRouteKey()]);

    foreach (WORKFLOW_ACTIONS as $action) {
        in_array($action, $expected, true) ? $page->assertActionVisible($action) : $page->assertActionHidden($action);
    }
})->with($cases);

it('hides approval from an editor on their own article', function (): void {
    $editor = AdminFixtures::user(Role::Editor);
    $article = AdminFixtures::article(ArticleStatus::InReview, $editor);
    $this->actingAs($editor);

    Livewire::test(ViewArticle::class, ['record' => $article->getRouteKey()])
        ->assertActionHidden('approve')
        ->assertActionVisible('return');
});

it('lets the writer submit from the list', function (): void {
    $article = AdminFixtures::article(ArticleStatus::Draft, $this->writer);
    $this->actingAs($this->writer);

    Livewire::test(ListArticles::class)->callAction(TestAction::make('submit')->table($article))->assertNotified();

    expect($article->refresh()->status)->toBe('in_review');
});

it('runs the review and publication actions end to end', function (): void {
    $editor = AdminFixtures::user(Role::Editor);
    $chief = AdminFixtures::user(Role::EditorInChief);
    $article = AdminFixtures::article(ArticleStatus::InReview, $this->writer);
    $view = fn () => Livewire::test(ViewArticle::class, ['record' => $article->getRouteKey()]);

    $this->actingAs($editor);
    $view()->callAction('return', data: ['note' => ''])->assertHasActionErrors(['note' => 'required']);
    $view()->callAction('return', data: ['note' => 'أضف مصدراً']);
    expect($article->refresh()->status)->toBe('returned')->and($article->return_note)->toBe('أضف مصدراً');

    $this->actingAs($this->writer);
    $view()->callAction('submit');

    $this->actingAs($editor);
    $view()->callAction('approve');
    expect($article->refresh()->status)->toBe('approved')->and($article->reviewed_by)->toBe($editor->id);

    $this->actingAs($chief);
    $view()->callAction('schedule', data: ['publish_at' => now()->subHour()->format('Y-m-d H:i:s')])->assertNotified();
    expect($article->refresh()->status)->toBe('approved');

    $view()->callAction('schedule', data: ['publish_at' => now()->addDay()->format('Y-m-d H:i:s')]);
    expect($article->refresh()->status)->toBe('scheduled');

    $view()->callAction('publish');
    expect($article->refresh()->status)->toBe('published')->and($article->published_at)->not->toBeNull();

    $view()->callAction('mark_breaking', data: ['until' => now()->addHours(3)->format('Y-m-d H:i:s')]);
    $view()->callAction('promote', data: ['featured' => true, 'homepage_priority' => 5]);
    expect($article->refresh()->is_breaking)->toBeTrue()
        ->and($article->breaking_until)->not->toBeNull()
        ->and($article->is_featured)->toBeTrue()
        ->and($article->homepage_priority)->toBe(5);

    $view()->callAction('unmark_breaking');
    $view()->callAction('unpublish');
    expect($article->refresh()->status)->toBe('unpublished')->and($article->is_breaking)->toBeFalse();

    expect(DB::table('activity_log')->where('subject_id', $article->id)->pluck('event')->all())->toContain(
        'returned_to_writer', 'approved', 'scheduled', 'published', 'marked_breaking', 'promoted', 'breaking_cleared', 'unpublished',
    );
});
