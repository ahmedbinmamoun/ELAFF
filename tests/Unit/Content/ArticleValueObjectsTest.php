<?php

declare(strict_types=1);

use Elaf\Content\Domain\Article\ArticleBody;
use Elaf\Content\Domain\Article\ArticleContent;
use Elaf\Content\Domain\Article\ArticleSource;
use Elaf\Content\Domain\Article\Exception\InvalidArticleContent;
use Elaf\Content\Domain\Article\FeaturedImage;
use Elaf\Content\Domain\Article\IssuePlacement;
use Elaf\Content\Domain\Article\SeriesPlacement;
use Elaf\Content\Domain\Article\Title;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Content\Domain\Series\SeriesId;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Shared\Domain\Text\Slug;
use Tests\Support\Builders\Articles;

it('normalises title whitespace and rejects empty or long titles', function (): void {
    expect((new Title("  عنوان\n  طويل "))->value)->toBe('عنوان طويل');
    expect(fn () => new Title('   '))->toThrow(InvalidArticleContent::class);
    expect(fn () => new Title(str_repeat('ب', Title::MAX_LENGTH + 1)))->toThrow(InvalidArticleContent::class);
});

it('measures the body as plain text', function (): void {
    $body = ArticleBody::fromSanitizedHtml('<h2>عنوان</h2><p>كلمة&nbsp;ثانية<br>ثالثة</p>');

    expect($body->plainText())->toBe('عنوان كلمة ثانية ثالثة')
        ->and($body->wordCount())->toBe(4)
        ->and($body->readingMinutes())->toBe(1)
        ->and(ArticleBody::fromSanitizedHtml('<p>'.str_repeat('كلمة ', 401).'</p>')->readingMinutes())->toBe(3);
});

it('treats a body with only markup as empty unless it holds an image', function (): void {
    expect(ArticleBody::empty()->isEmpty())->toBeTrue()
        ->and(ArticleBody::fromSanitizedHtml('<p> </p><br>')->isEmpty())->toBeTrue()
        ->and(ArticleBody::fromSanitizedHtml('<figure><img src="/a.webp" alt=""></figure>')->isEmpty())->toBeFalse();
});

it('validates the source', function (): void {
    $source = new ArticleSource(' رويترز ', ' https://reuters.com/x ');
    expect($source->name)->toBe('رويترز')->and($source->url)->toBe('https://reuters.com/x');
    expect((new ArticleSource('إيلاف', ''))->url)->toBeNull();

    expect(fn () => new ArticleSource(''))->toThrow(InvalidArticleContent::class);
    expect(fn () => new ArticleSource('x', 'javascript:alert(1)'))->toThrow(InvalidArticleContent::class);
});

it('keeps image caption and credit optional', function (): void {
    $image = new FeaturedImage('a/b.webp', '  ', ' تصوير: إيلاف ');

    expect($image->caption)->toBeNull()->and($image->credit)->toBe('تصوير: إيلاف');
    expect(fn () => new FeaturedImage(' '))->toThrow(InvalidArticleContent::class);
});

it('requires positive series parts and issue pages', function (): void {
    expect(fn () => new SeriesPlacement(SeriesId::fromInt(1), 0))->toThrow(InvalidArticleContent::class);
    expect(fn () => new IssuePlacement(IssueId::fromInt(1), 0))->toThrow(InvalidArticleContent::class);
});

it('blanks optional text and checks lengths and urls', function (): void {
    $content = new ArticleContent(
        title: new Title('عنوان'),
        slug: Slug::fromText('عنوان'),
        body: ArticleBody::empty(),
        source: new ArticleSource('إيلاف'),
        categoryId: CategoryId::fromInt(1),
        kicker: '  ',
        dateline: ' الخرطوم ',
    );
    expect($content->kicker)->toBeNull()->and($content->dateline)->toBe('الخرطوم');

    $base = fn (array $extra) => new ArticleContent(...[
        'title' => new Title('عنوان'),
        'slug' => Slug::fromText('عنوان'),
        'body' => ArticleBody::empty(),
        'source' => new ArticleSource('إيلاف'),
        'categoryId' => CategoryId::fromInt(1),
        ...$extra,
    ]);

    expect(fn () => $base(['dateline' => str_repeat('a', 101)]))->toThrow(InvalidArticleContent::class);
    expect(fn () => $base(['canonicalUrl' => 'ftp://x.y']))->toThrow(InvalidArticleContent::class);
    expect(fn () => $base(['authorIds' => [AuthorId::fromInt(1), AuthorId::fromInt(1)]]))->toThrow(InvalidArticleContent::class);
    expect(fn () => $base(['tagIds' => [TagId::fromInt(2), TagId::fromInt(2)]]))->toThrow(InvalidArticleContent::class);
});

it('keeps byline order but stores tags as a sorted set', function (): void {
    $content = Articles::content(authorIds: [3, 1, 2], tagIds: [9, 4, 6]);

    expect($content->toArray()['author_ids'])->toBe([3, 1, 2])
        ->and($content->toArray()['tag_ids'])->toBe([4, 6, 9]);
});

it('round-trips through its snapshot array (revisions)', function (): void {
    $content = new ArticleContent(
        title: new Title('عنوان'),
        slug: Slug::fromText('عنوان'),
        body: ArticleBody::fromSanitizedHtml('<p>نص</p>'),
        source: new ArticleSource('رويترز', 'https://example.com/a'),
        categoryId: CategoryId::fromInt(2),
        authorIds: [AuthorId::fromInt(5), AuthorId::fromInt(4)],
        tagIds: [TagId::fromInt(1)],
        kicker: 'عاجل',
        subtitle: 'عنوان فرعي',
        dateline: 'الخرطوم',
        excerpt: 'مقتطف',
        featuredImage: new FeaturedImage('img/a.webp', 'تعليق', 'إيلاف'),
        series: new SeriesPlacement(SeriesId::fromInt(3), 2),
        seoTitle: 'عنوان محركات البحث',
        seoDescription: 'وصف',
        canonicalUrl: 'https://elaf.example/article/1',
        printSection: 'تحقيق',
    );

    $restored = ArticleContent::fromArray(json_decode((string) json_encode($content->toArray()), true));

    expect($restored->equals($content))->toBeTrue()
        ->and($restored->diff($content))->toBe([]);
});

it('reads snapshots stored before print_section existed', function (): void {
    $old = Articles::content()->toArray();
    unset($old['print_section']);

    expect(ArticleContent::fromArray($old)->printSection)->toBeNull();
});

it('rejects malformed snapshots', function (array $data): void {
    ArticleContent::fromArray([...Articles::content()->toArray(), ...$data]);
})->with([
    'title not a string' => [['title' => 5]],
    'category not an int' => [['category_id' => '1']],
    'authors not a list' => [['author_ids' => ['a' => 1]]],
    'author ids not ints' => [['author_ids' => ['1']]],
])->throws(InvalidArticleContent::class);

it('lists what is missing for publication', function (): void {
    expect(Articles::content()->missingForPublication())->toBe([])
        ->and(Articles::content(body: '', authorIds: [])->missingForPublication())->toBe(['body or featured image', 'author']);
});
