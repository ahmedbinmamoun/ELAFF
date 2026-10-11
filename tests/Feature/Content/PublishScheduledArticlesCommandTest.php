<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\CategorySeeder;
use Elaf\Content\Domain\Article\ArticleRepository;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Domain\Article\Events\ArticlePublished;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Time\Clock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Support\Builders\Articles;
use Tests\Support\Fakes\FrozenClock;

uses(RefreshDatabase::class);

it('publishes due scheduled articles every minute (FR-ART-05)', function (): void {
    Event::fake([ArticlePublished::class]);
    $this->seed(CategorySeeder::class);
    $clock = new FrozenClock('2026-03-05 10:00:00');
    $this->app->instance(Clock::class, $clock);
    [$writer, $editor] = User::factory()->count(2)->create()->all();
    $author = AuthorModel::query()->create(['name' => 'كاتب', 'slug' => 'katib'])->id;
    $content = Articles::content(categoryId: (int) CategoryModel::query()->value('id'), authorIds: [$author]);

    $repository = app(ArticleRepository::class);
    $article = Elaf\Content\Domain\Article\Article::draft($repository->nextId(), Elaf\Content\Domain\Article\ArticleOrigin::Staff, UserId::fromInt($writer->id), $content, $clock);
    $article->submitForReview(UserId::fromInt($writer->id), $clock);
    $article->approve(UserId::fromInt($editor->id), $clock);
    $article->schedule($clock->now()->modify('+10 minutes'), UserId::fromInt($editor->id), $clock);
    $article->releaseEvents();
    $repository->save($article);

    $this->artisan('articles:publish-scheduled')->assertSuccessful();
    expect(ArticleModel::query()->findOrFail($article->id()->value)->status)->toBe(ArticleStatus::Scheduled->value);
    Event::assertNotDispatched(ArticlePublished::class);

    $clock->advance('PT11M');
    $this->artisan('articles:publish-scheduled')
        ->expectsOutputToContain("Published article {$article->id()->value}.")
        ->assertSuccessful();

    $model = ArticleModel::query()->findOrFail($article->id()->value);
    expect($model->status)->toBe('published')
        ->and($model->published_at?->format('Y-m-d H:i'))->toBe('2026-03-05 10:10');
    Event::assertDispatched(ArticlePublished::class);
});
