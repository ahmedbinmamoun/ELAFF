<?php

declare(strict_types=1);

use Elaf\Content\Application\Article\CreateArticleDraft\CreateArticleDraftCommand;
use Elaf\Content\Application\Article\CreateArticleDraft\CreateArticleDraftHandler;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Domain\Article\Events\ArticleCreated;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\InvalidInput;
use Tests\Support\ContentKit;

beforeEach(function (): void {
    $this->kit = (new ContentKit)->grant(ContentPermission::CreateArticles);
    $this->create = fn (array $overrides = [], ArticleOrigin $origin = ArticleOrigin::Staff) => $this->kit
        ->make(CreateArticleDraftHandler::class)
        ->handle(new CreateArticleDraftCommand(1, $origin, $this->kit->input($overrides)));
});

it('creates a draft with all FR-ART-01 fields, a first revision and an event', function (): void {
    $series = $this->kit->aSeries();
    $id = ($this->create)([
        'kicker' => 'خاص',
        'subtitle' => 'عنوان فرعي',
        'dateline' => 'الخرطوم',
        'excerpt' => 'مقتطف',
        'featuredImagePath' => 'articles/1.webp',
        'featuredImageCaption' => 'تعليق الصورة',
        'featuredImageCredit' => 'إيلاف',
        'sourceUrl' => 'https://example.com',
        'seriesId' => $series,
        'seriesPart' => 2,
        'tagNames' => ['اقتصاد', 'السوق'],
    ]);

    $article = $this->kit->articles->get($id);
    $content = $article->content();

    expect($article->status())->toBe(ArticleStatus::Draft)
        ->and($article->createdBy()->value)->toBe(1)
        ->and($content->title->value)->toBe('عنوان المقال')
        ->and($content->slug->value)->toBe('عنوان-المقال')
        ->and($content->kicker)->toBe('خاص')
        ->and($content->dateline)->toBe('الخرطوم')
        ->and($content->featuredImage?->caption)->toBe('تعليق الصورة')
        ->and($content->source->url)->toBe('https://example.com')
        ->and($content->series?->part)->toBe(2)
        ->and($content->tagIds)->toHaveCount(2)
        ->and($this->kit->revisions->forArticle($id))->toHaveCount(1)
        ->and($this->kit->events->ofType(ArticleCreated::class))->toHaveCount(1)
        ->and($this->kit->transaction->runs)->toBeGreaterThan(0);
});

it('requires the create permission', function (): void {
    $this->kit->authorizer->revoke(ContentPermission::CreateArticles->value);

    expect(fn () => ($this->create)())->toThrow(NotAuthorized::class);
    expect($this->kit->articles->saves)->toBe(0);
});

it('sanitizes the body on save (FR-ART-03)', function (): void {
    $id = ($this->create)(['body' => '<p onclick="steal()">نص</p><script>alert(1)</script><iframe src="https://x.y"></iframe>']);

    expect($this->kit->articles->get($id)->content()->body->html)->toBe('<p>نص</p>');
});

it('uses the strict profile for contributors', function (): void {
    $category = $this->kit->category('رأي', openToContributors: true);
    $this->kit->authors->create(new Elaf\Content\Application\Author\AuthorData('ضيف', Elaf\Shared\Domain\Text\Slug::fromText('ضيف'), userId: Elaf\Identity\Domain\UserId::fromInt(1)));
    $id = ($this->create)([
        'categoryId' => $category,
        'body' => '<p class="x"><a href="https://example.com" target="_blank">رابط</a></p>',
    ], ArticleOrigin::Contributor);

    expect($this->kit->articles->get($id)->content()->body->html)
        ->toBe('<p><a href="https://example.com" rel="nofollow">رابط</a></p>');
});

it('only lets contributors use categories open to them (FR-CON-06)', function (): void {
    $closed = $this->kit->category('أخبار');
    $this->kit->authors->create(new Elaf\Content\Application\Author\AuthorData('ضيف', Elaf\Shared\Domain\Text\Slug::fromText('ضيف'), userId: Elaf\Identity\Domain\UserId::fromInt(1)));

    expect(fn () => ($this->create)(['categoryId' => $closed], ArticleOrigin::Contributor))
        ->toThrow(InvalidInput::class, 'not open to contributors');
});

it('resolves free-form tags, reusing existing ones by slug (FR-TAX-02)', function (): void {
    $first = ($this->create)(['tagNames' => ['اقتصاد', ' ', 'السوق']]);
    $second = ($this->create)(['tagNames' => ['السوق', 'اقتصاد', 'جديد']]);

    $firstTags = $this->kit->articles->get($first)->content()->tagIds;
    $secondTags = $this->kit->articles->get($second)->content()->tagIds;

    expect($firstTags)->toHaveCount(2)
        ->and($secondTags)->toHaveCount(3)
        ->and(array_intersect(array_column($firstTags, 'value'), array_column($secondTags, 'value')))->toHaveCount(2);
});

it('reports invalid input on the right field', function (array $overrides, string $field): void {
    try {
        ($this->create)($overrides);
        $this->fail('Expected InvalidInput.');
    } catch (InvalidInput $e) {
        expect($e->field)->toBe($field);
    }

    expect($this->kit->articles->saves)->toBe(0);
})->with([
    'blank title' => [['title' => '  '], 'title'],
    'unknown category' => [['categoryId' => 999], 'category_id'],
    'unknown author' => [['authorIds' => [999]], 'author_ids'],
    'unknown series' => [['seriesId' => 999, 'seriesPart' => 1], 'series_id'],
    'bad source url' => [['sourceUrl' => 'not a url'], 'source_url'],
    'bad canonical url' => [['canonicalUrl' => 'javascript:x'], 'canonical_url'],
    'malformed slug' => [['slug' => 'Bad Slug'], 'slug'],
    'too many tags' => [['tagNames' => array_map(static fn (int $i): string => "وسم {$i}", range(1, 21))], 'tags'],
    'body too long' => [['body' => str_repeat('a', 200_001)], 'body'],
]);

it('needs a part number for series articles', function (): void {
    $series = $this->kit->aSeries();

    expect(fn () => ($this->create)(['seriesId' => $series]))->toThrow(InvalidInput::class, 'part number');
});
