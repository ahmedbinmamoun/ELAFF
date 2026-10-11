<?php

declare(strict_types=1);

use Elaf\Shared\Domain\Exception\InvalidSlug;
use Elaf\Shared\Domain\Text\Slug;

it('derives Arabic slugs from text (FR-ART-10)', function (string $text, string $slug): void {
    expect(Slug::fromText($text)->value)->toBe($slug);
})->with([
    'arabic words' => ['أخبار الأسبوع', 'أخبار-الأسبوع'],
    'tashkeel and tatweel removed' => ['السُّـوقُ الحُرَّة', 'السوق-الحرة'],
    'punctuation collapses' => ['كيف نفهم الحرب والسلام (3)', 'كيف-نفهم-الحرب-والسلام-3'],
    'latin lower-cased' => ['  Sudan  Economy 2026! ', 'sudan-economy-2026'],
    'mixed' => ['تقرير: GDP يرتفع', 'تقرير-gdp-يرتفع'],
]);

it('caps derived slugs at the maximum length without a trailing hyphen', function (): void {
    $slug = Slug::fromText(str_repeat('كلمة ', 60));

    expect(mb_strlen($slug->value))->toBeLessThanOrEqual(Slug::MAX_LENGTH)
        ->and(str_ends_with($slug->value, '-'))->toBeFalse();
});

it('rejects text without letters or digits', function (string $text): void {
    Slug::fromText($text);
})->with(['', '   ', '!!! ---', 'ـــ'])->throws(InvalidSlug::class);

it('accepts only well-formed slugs as-is', function (string $value, bool $valid): void {
    $attempt = static fn () => Slug::fromString($value);

    $valid ? expect($attempt()->value)->toBe($value) : expect($attempt)->toThrow(InvalidSlug::class);
})->with([
    ['أخبار-الأسبوع', true],
    ['news-2026', true],
    ['News', false],
    ['two--hyphens', false],
    ['-leading', false],
    ['with space', false],
    ['', false],
]);
