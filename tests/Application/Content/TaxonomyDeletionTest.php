<?php

declare(strict_types=1);

use Elaf\Content\Application\Author\DeleteAuthor\DeleteAuthorCommand;
use Elaf\Content\Application\Author\DeleteAuthor\DeleteAuthorHandler;
use Elaf\Content\Application\Category\CategoryData;
use Elaf\Content\Application\Category\DeleteCategory\DeleteCategoryCommand;
use Elaf\Content\Application\Category\DeleteCategory\DeleteCategoryHandler;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Application\Series\DeleteSeries\DeleteSeriesCommand;
use Elaf\Content\Application\Series\DeleteSeries\DeleteSeriesHandler;
use Elaf\Content\Application\Tag\DeleteTag\DeleteTagCommand;
use Elaf\Content\Application\Tag\DeleteTag\DeleteTagHandler;
use Elaf\Content\Application\Tag\RenameTag\RenameTagCommand;
use Elaf\Content\Application\Tag\RenameTag\RenameTagHandler;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Content\Domain\Series\SeriesId;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Application\Validation\RecordNotFound;
use Elaf\Shared\Domain\Text\Slug;
use Tests\Support\ContentKit;

beforeEach(function (): void {
    $this->kit = (new ContentKit)->grant(ContentPermission::ManageTaxonomy, ContentPermission::ManageAuthors);
    $this->reason = static function (callable $work): string {
        try {
            $work();
        } catch (InvalidInput $e) {
            return $e->reason;
        }

        return 'none';
    };
});

it('deletes an empty category but keeps one with articles or subcategories', function (): void {
    $used = CategoryId::fromInt($this->kit->category('أخبار'));
    $parent = CategoryId::fromInt($this->kit->category('تقارير'));
    $this->kit->categories->create(new CategoryData('فرعي', Slug::fromText('فرعي'), parentId: $parent));
    $empty = CategoryId::fromInt($this->kit->category('أخيرة'));
    $this->kit->categories->markUsedByArticle($used);
    $delete = fn (CategoryId $id) => $this->kit->make(DeleteCategoryHandler::class)->handle(new DeleteCategoryCommand($id->value));

    expect(($this->reason)(fn () => $delete($used)))->toBe('category_has_articles')
        ->and(($this->reason)(fn () => $delete($parent)))->toBe('category_has_children');

    $delete($empty);
    expect($this->kit->categories->find($empty))->toBeNull()
        ->and($this->kit->categories->find($used))->not->toBeNull();
    expect(fn () => $delete($empty))->toThrow(RecordNotFound::class);
});

it('deletes series and authors only when no article uses them', function (): void {
    $series = SeriesId::fromInt($this->kit->aSeries());
    $author = AuthorId::fromInt($this->kit->author());

    $this->kit->series->markUsedByArticle($series);
    $this->kit->authors->markUsedByArticle($author);

    expect(($this->reason)(fn () => $this->kit->make(DeleteSeriesHandler::class)->handle(new DeleteSeriesCommand($series->value))))->toBe('series_has_articles')
        ->and(($this->reason)(fn () => $this->kit->make(DeleteAuthorHandler::class)->handle(new DeleteAuthorCommand($author->value))))->toBe('author_has_articles');

    $free = AuthorId::fromInt($this->kit->author('كاتب آخر'));
    $this->kit->make(DeleteAuthorHandler::class)->handle(new DeleteAuthorCommand($free->value));
    expect($this->kit->authors->find($free))->toBeNull();
});

it('renames a tag with its slug and refuses a name another tag already has', function (): void {
    $gold = $this->kit->tags->create('الذهب', Slug::fromText('الذهب'));
    $this->kit->tags->create('الدولار', Slug::fromText('الدولار'));
    $rename = fn (string $name) => $this->kit->make(RenameTagHandler::class)->handle(new RenameTagCommand($gold->value, $name));

    $rename('أسعار الذهب');
    expect($this->kit->tags->find($gold)?->slug->value)->toBe('أسعار-الذهب');

    expect(($this->reason)(fn () => $rename('الدولار')))->toBe('slug_taken');
    $rename('أسعار الذهب');
});

it('deletes tags', function (): void {
    $tag = $this->kit->tags->create('الذهب', Slug::fromText('الذهب'));
    $this->kit->make(DeleteTagHandler::class)->handle(new DeleteTagCommand($tag->value));

    expect($this->kit->tags->find($tag))->toBeNull();
    expect(fn () => $this->kit->make(DeleteTagHandler::class)->handle(new DeleteTagCommand(TagId::fromInt(99)->value)))->toThrow(RecordNotFound::class);
});

it('requires the taxonomy and authors permissions', function (): void {
    $kit = new ContentKit;
    $category = $kit->category();
    $author = $kit->author();

    expect(fn () => $kit->make(DeleteCategoryHandler::class)->handle(new DeleteCategoryCommand($category)))->toThrow(NotAuthorized::class);
    expect(fn () => $kit->make(DeleteAuthorHandler::class)->handle(new DeleteAuthorCommand($author)))->toThrow(NotAuthorized::class);
    expect(fn () => $kit->make(RenameTagHandler::class)->handle(new RenameTagCommand(1, 'x')))->toThrow(NotAuthorized::class);
});
