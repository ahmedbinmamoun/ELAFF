<?php

declare(strict_types=1);

use Database\Seeders\CategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleRevisionModel;
use Elaf\Content\Presentation\Filament\Resources\Articles\Pages\EditArticle;
use Elaf\Content\Presentation\Filament\Resources\Articles\RelationManagers\HistoryRelationManager;
use Elaf\Content\Presentation\Filament\Resources\Articles\RelationManagers\RevisionsRelationManager;
use Elaf\Identity\Application\Role;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\AdminFixtures;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, CategorySeeder::class]);
    Filament::setCurrentPanel('admin');
    $this->writer = AdminFixtures::user(Role::Writer);
    $this->article = AdminFixtures::article(ArticleStatus::Draft, $this->writer, title: 'الأصل');

    $this->actingAs($this->writer);
    foreach (['الثانية', 'الثالثة'] as $title) {
        Livewire::test(EditArticle::class, ['record' => $this->article->getRouteKey()])->fillForm(['title' => $title])->call('save');
    }
});

it('lists revisions and lets an editor restore one (FR-ART-08)', function (): void {
    $editor = AdminFixtures::user(Role::Editor);
    $this->actingAs($editor);
    $second = ArticleRevisionModel::query()->where('snapshot->title', 'الثانية')->sole();

    Livewire::test(RevisionsRelationManager::class, ['ownerRecord' => $this->article, 'pageClass' => EditArticle::class])
        ->assertCanSeeTableRecords(ArticleRevisionModel::query()->get())
        ->callAction(TestAction::make('restore')->table($second))
        ->assertNotified();

    expect($this->article->refresh()->title)->toBe('الثانية')
        ->and(ArticleRevisionModel::query()->latest('id')->value('user_id'))->toBe($editor->id);
});

it('does not offer restore to writers', function (): void {
    $revision = ArticleRevisionModel::query()->first();

    Livewire::test(RevisionsRelationManager::class, ['ownerRecord' => $this->article, 'pageClass' => EditArticle::class])
        ->assertActionHidden(TestAction::make('restore')->table($revision))
        ->assertActionVisible(TestAction::make('preview')->table($revision));
});

it('shows the audit log of the article with actor and changed fields', function (): void {
    Livewire::test(HistoryRelationManager::class, ['ownerRecord' => $this->article, 'pageClass' => EditArticle::class])
        ->assertCanSeeTableRecords($this->article->activities()->get())
        ->assertSee('تعديل')
        ->assertSee('الحقول المعدلة: العنوان')
        ->assertSee($this->writer->name);
});
