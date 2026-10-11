<?php

declare(strict_types=1);

use Elaf\Content\Application\Article\ArticleCounts;
use Elaf\Content\Domain\Article\Article;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Domain\Article\ArticleRepository;
use Elaf\Content\Infrastructure\Persistence\Eloquent\EloquentArticleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Builders\Articles;
use Tests\Support\Contract\ContentFixtures;
use Tests\Support\Contract\EloquentContentFixtures;
use Tests\Support\Contract\InMemoryContentFixtures;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\InMemoryArticleRepository;

uses(RefreshDatabase::class);

dataset('article counts', [
    'in-memory' => fn (): array => [new InMemoryArticleRepository, new InMemoryContentFixtures],
    'eloquent' => fn (): array => [app(EloquentArticleRepository::class), new EloquentContentFixtures],
]);

it('counts articles in review and lists published ones per writer', function (ArticleCounts&ArticleRepository $repository, ContentFixtures $fx): void {
    $clock = new FrozenClock;
    $writer = $fx->user();
    $other = $fx->user();
    $editor = $fx->user();
    $content = Articles::content(categoryId: $fx->category()->value, authorIds: [$fx->author()->value]);
    $make = static function (Elaf\Identity\Domain\UserId $by, string $step) use ($repository, $content, $clock, $editor): Article {
        $a = Article::draft($repository->nextId(), ArticleOrigin::Contributor, $by, $content, $clock);
        if ($step !== 'draft') {
            $a->submitForReview($by, $clock);
        }
        if ($step === 'published') {
            $a->approve($editor, $clock);
            $a->publish($editor, $clock);
        }
        $repository->save($a);

        return $a;
    };

    $make($writer, 'in_review');
    $make($writer, 'in_review');
    $make($writer, 'draft');
    $first = $make($writer, 'published');
    $second = $make($writer, 'published');
    $make($other, 'in_review');

    expect($repository->inReviewBy($writer))->toBe(2)
        ->and($repository->inReviewBy($editor))->toBe(0)
        ->and($repository->publishedBy($writer))->toEqual([$first->id(), $second->id()])
        ->and($repository->publishedBy($other))->toBe([]);
})->with('article counts');
