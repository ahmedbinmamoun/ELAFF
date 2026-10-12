<?php

declare(strict_types=1);

use Database\Factories\Support\DemoPdf;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Presentation\Filament\Resources\Articles\Pages\EditArticle;
use Elaf\Identity\Application\Role;
use Elaf\Publishing\Infrastructure\Persistence\IssueModel;
use Elaf\Publishing\Infrastructure\Processing\ProcessIssuePdfJob;
use Elaf\Publishing\Presentation\Filament\Issues\Pages\CreateIssue;
use Elaf\Publishing\Presentation\Filament\Issues\Pages\EditIssue;
use Elaf\Publishing\Presentation\Filament\Issues\Pages\ListIssues;
use Elaf\Publishing\Presentation\Filament\Issues\Pages\ViewIssue;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\AdminFixtures;
use Tests\Support\IssueFixtures;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, CategorySeeder::class]);
    Filament::setCurrentPanel('admin');
    Storage::fake('public');
    Queue::fake();
});

it('lets only the Editor-in-Chief and Admin manage issues (PRD §2)', function (Role $role, bool $allowed): void {
    $this->actingAs(AdminFixtures::user($role));

    $this->get('/admin/issues')->assertStatus($allowed ? 200 : 403);
})->with([
    'writer' => [Role::Writer, false],
    'editor' => [Role::Editor, false],
    'editor-in-chief' => [Role::EditorInChief, true],
    'admin' => [Role::Admin, true],
]);

it('uploads a PDF: stored, pending, rendering queued (FR-ISS-01, 02)', function (): void {
    $this->actingAs(AdminFixtures::user(Role::EditorInChief));
    IssueFixtures::ready(119);

    Livewire::test(CreateIssue::class)
        ->assertFormSet(['number' => 120])
        ->fillForm([
            'number' => 120,
            'published_on' => '2026-10-08',
            'title' => 'عدد تجريبي',
            'pdf' => UploadedFile::fake()->createWithContent('scan.pdf', DemoPdf::blank(2)),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $issue = IssueModel::query()->where('number', 120)->firstOrFail();
    expect($issue->processing_status)->toBe('pending')
        ->and($issue->title)->toBe('عدد تجريبي');
    Storage::disk('public')->assertExists($issue->pdf_path);
    Queue::assertPushed(ProcessIssuePdfJob::class, static fn (ProcessIssuePdfJob $job): bool => $job->issueId === $issue->id);
});

it('rejects a file that is not a PDF, whatever its name, and duplicate numbers', function (): void {
    $this->actingAs(AdminFixtures::user(Role::EditorInChief));
    IssueFixtures::ready(119);

    Livewire::test(CreateIssue::class)
        ->fillForm(['number' => 121, 'published_on' => '2026-10-15', 'pdf' => UploadedFile::fake()->createWithContent('fake.pdf', '<?php echo 1;')])
        ->call('create')
        ->assertHasFormErrors(['pdf']);

    Livewire::test(CreateIssue::class)
        ->fillForm(['number' => 119, 'published_on' => '2026-10-15', 'pdf' => UploadedFile::fake()->createWithContent('a.pdf', DemoPdf::blank(1))])
        ->call('create')
        ->assertHasFormErrors(['number']);

    expect(IssueModel::query()->count())->toBe(1);
    Queue::assertNothingPushed();
});

it('edits details and replaces the PDF through the use case', function (): void {
    $this->actingAs(AdminFixtures::user(Role::EditorInChief));
    $issue = IssueFixtures::ready(120);

    Livewire::test(EditIssue::class, ['record' => $issue->id])
        ->fillForm(['published_on' => '2026-10-09', 'title' => 'تصحيح'])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($issue->refresh()->processing_status)->toBe('ready')->and($issue->title)->toBe('تصحيح');
    Queue::assertNothingPushed();

    Livewire::test(EditIssue::class, ['record' => $issue->id])
        ->fillForm(['pdf' => UploadedFile::fake()->createWithContent('new.pdf', DemoPdf::blank(3))])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($issue->refresh()->processing_status)->toBe('pending')->and($issue->page_count)->toBeNull();
    Queue::assertPushed(ProcessIssuePdfJob::class);
});

it('shows why processing failed and retries it (FR-ISS-06)', function (): void {
    $this->actingAs(AdminFixtures::user(Role::EditorInChief));
    $issue = IssueFixtures::failed(120, 'pdf_unreadable');

    Livewire::test(ListIssues::class)->assertCanSeeTableRecords([$issue])->assertSee('فشلت المعالجة');
    Livewire::test(ViewIssue::class, ['record' => $issue->id])
        ->assertSee('تعذّرت قراءة ملف PDF')
        ->callAction('retry');

    expect($issue->refresh()->processing_status)->toBe('pending');
    Queue::assertPushed(ProcessIssuePdfJob::class);
});

it('deletes an issue only when no article is linked', function (): void {
    $this->actingAs(AdminFixtures::user(Role::EditorInChief));
    $linked = IssueFixtures::ready(119);
    $free = IssueFixtures::ready(120);
    $article = AdminFixtures::article(ArticleStatus::Published, AdminFixtures::user(Role::Writer));
    $article->forceFill(['issue_id' => $linked->id, 'issue_page' => 1])->save();

    Livewire::test(ListIssues::class)->callAction(TestAction::make('delete')->table($linked));
    Livewire::test(ListIssues::class)->callAction(TestAction::make('delete')->table($free));

    expect(IssueModel::query()->pluck('number')->all())->toBe([119]);
});

it('links an article to an issue page from the article screen (FR-ART-01)', function (): void {
    $this->actingAs(AdminFixtures::user(Role::Editor));
    $issue = IssueFixtures::ready(120, pages: 8);
    $article = AdminFixtures::article(ArticleStatus::Published, AdminFixtures::user(Role::Writer));

    Livewire::test(EditArticle::class, ['record' => $article->id])
        ->callAction('assign_issue', ['issue_id' => $issue->id, 'issue_page' => 9])
        ->assertHasNoActionErrors();
    expect($article->refresh()->issue_id)->toBeNull();

    Livewire::test(EditArticle::class, ['record' => $article->id])
        ->callAction('assign_issue', ['issue_id' => $issue->id, 'issue_page' => 5]);
    expect($article->refresh()->issue_id)->toBe($issue->id)->and($article->issue_page)->toBe(5);

    $this->actingAs(AdminFixtures::user(Role::EditorInChief));
    Livewire::test(ViewIssue::class, ['record' => $issue->id])->assertSee('ص 5 — '.$article->title);
});
