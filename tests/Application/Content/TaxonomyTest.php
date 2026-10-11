<?php

declare(strict_types=1);

use Elaf\Content\Application\Category\CategoryInput;
use Elaf\Content\Application\Category\CreateCategory\CreateCategoryCommand;
use Elaf\Content\Application\Category\CreateCategory\CreateCategoryHandler;
use Elaf\Content\Application\Category\UpdateCategory\UpdateCategoryCommand;
use Elaf\Content\Application\Category\UpdateCategory\UpdateCategoryHandler;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Application\Series\CreateSeries\CreateSeriesCommand;
use Elaf\Content\Application\Series\CreateSeries\CreateSeriesHandler;
use Elaf\Content\Application\Series\SeriesInput;
use Elaf\Content\Application\Series\UpdateSeries\UpdateSeriesCommand;
use Elaf\Content\Application\Series\UpdateSeries\UpdateSeriesHandler;
use Elaf\Content\Application\Tag\EnsureTagsExist\EnsureTagsExistCommand;
use Elaf\Content\Application\Tag\EnsureTagsExist\EnsureTagsExistHandler;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Application\Validation\RecordNotFound;
use Tests\Support\ContentKit;

beforeEach(function (): void {
    $this->kit = (new ContentKit)->grant(ContentPermission::ManageTaxonomy, ContentPermission::CreateArticles);
    $this->createCategory = fn (CategoryInput $input): CategoryId => $this->kit->make(CreateCategoryHandler::class)->handle(new CreateCategoryCommand($input));
    $this->updateCategory = fn (CategoryId $id, CategoryInput $input) => $this->kit->make(UpdateCategoryHandler::class)->handle(new UpdateCategoryCommand($id->value, $input));
});

it('creates a category with a slug derived from its Arabic name (FR-TAX-01)', function (): void {
    $id = ($this->createCategory)(new CategoryInput('رأي اقتصادي', description: ' مقالات ', sortOrder: 80, isOpenToContributors: true));
    $category = $this->kit->categories->find($id);

    expect($category?->data->slug->value)->toBe('رأي-اقتصادي')
        ->and($category?->data->description)->toBe('مقالات')
        ->and($category?->data->sortOrder)->toBe(80)
        ->and($category?->data->isOpenToContributors)->toBeTrue()
        ->and($category?->data->isVisible)->toBeTrue();
});

it('keeps category slugs unique, except for the category itself', function (): void {
    $id = ($this->createCategory)(new CategoryInput('أسواق'));

    expect(fn () => ($this->createCategory)(new CategoryInput('سوق', slug: 'أسواق')))->toThrow(InvalidInput::class, 'already in use');

    ($this->updateCategory)($id, new CategoryInput('الأسواق', slug: 'أسواق'));
    expect($this->kit->categories->find($id)?->data->name)->toBe('الأسواق');
});

it('nests categories without cycles', function (): void {
    $root = ($this->createCategory)(new CategoryInput('أخبار'));
    $child = ($this->createCategory)(new CategoryInput('محلية', parentId: $root->value));
    $grandchild = ($this->createCategory)(new CategoryInput('الخرطوم', parentId: $child->value));

    expect($this->kit->categories->find($grandchild)?->data->parentId?->value)->toBe($child->value);
    expect(fn () => ($this->updateCategory)($root, new CategoryInput('أخبار', parentId: $grandchild->value)))->toThrow(InvalidInput::class, 'itself');
    expect(fn () => ($this->updateCategory)($root, new CategoryInput('أخبار', parentId: $root->value)))->toThrow(InvalidInput::class, 'itself');
    expect(fn () => ($this->createCategory)(new CategoryInput('يتيم', parentId: 99)))->toThrow(InvalidInput::class);
});

it('requires the taxonomy permission and an existing category to update', function (): void {
    expect(fn () => ($this->updateCategory)(CategoryId::fromInt(5), new CategoryInput('x')))->toThrow(RecordNotFound::class);

    $this->kit->authorizer->revoke(ContentPermission::ManageTaxonomy->value);
    expect(fn () => ($this->createCategory)(new CategoryInput('x')))->toThrow(NotAuthorized::class);
});

it('creates and updates series (FR-TAX-03)', function (): void {
    $id = $this->kit->make(CreateSeriesHandler::class)->handle(new CreateSeriesCommand(new SeriesInput('كيف نفهم الحرب والسلام')));
    expect($this->kit->series->find($id)?->data->slug->value)->toBe('كيف-نفهم-الحرب-والسلام');

    $this->kit->make(UpdateSeriesHandler::class)->handle(new UpdateSeriesCommand($id->value, new SeriesInput('عنوان آخر', description: 'وصف')));
    expect($this->kit->series->find($id)?->data->description)->toBe('وصف');

    expect(fn () => $this->kit->make(UpdateSeriesHandler::class)->handle(new UpdateSeriesCommand(9, new SeriesInput('x'))))
        ->toThrow(RecordNotFound::class);
    expect(fn () => $this->kit->make(CreateSeriesHandler::class)->handle(new CreateSeriesCommand(new SeriesInput('عنوان آخر'))))
        ->toThrow(InvalidInput::class);
});

it('creates missing tags once and suggests them by prefix (FR-TAX-02)', function (): void {
    $handler = $this->kit->make(EnsureTagsExistHandler::class);
    $first = $handler->handle(new EnsureTagsExistCommand(['الذهب', 'الذهب ', 'الدولار']));
    $second = $handler->handle(new EnsureTagsExistCommand(['الدولار', 'القمح']));

    $values = static fn (array $ids): array => array_map(static fn (TagId $id): int => $id->value, $ids);
    expect($values($first))->toBe([1, 2])
        ->and($values($second))->toBe([2, 3])
        ->and(array_map(static fn ($t): string => $t->name, $this->kit->tags->suggest('الد')))->toBe(['الدولار'])
        ->and($this->kit->tags->suggest('  '))->toBe([]);
});
