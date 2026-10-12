<?php

declare(strict_types=1);

namespace Elaf\Engagement\Infrastructure\Content;

use Elaf\Content\Application\Article\ArticleInput;
use Elaf\Content\Application\Article\CreateArticleDraft\CreateArticleDraftCommand;
use Elaf\Content\Application\Article\CreateArticleDraft\CreateArticleDraftHandler;
use Elaf\Content\Application\Author\AuthorData;
use Elaf\Content\Application\Author\AuthorRepository;
use Elaf\Content\Application\Category\CategoryData;
use Elaf\Content\Application\Category\CategoryRepository;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Engagement\Application\Letter\Convert\LetterArticles;
use Elaf\Engagement\Application\Letter\Convert\LetterDraft;
use Elaf\Shared\Domain\Text\Slug;

/**
 * Letters become drafts through Content's own use case (its checks apply),
 * in the "بريد القراء" section under the "بريد القراء" byline. Both are
 * created on first use when missing (system action, no extra permission).
 */
final readonly class ContentLetterArticles implements LetterArticles
{
    public const SECTION = 'بريد القراء';

    public function __construct(
        private CreateArticleDraftHandler $drafts,
        private CategoryRepository $categories,
        private AuthorRepository $authors,
    ) {}

    public function createDraft(LetterDraft $draft, int $actorId): ArticleId
    {
        $body = implode('', array_map(static fn (string $p): string => '<p>'.e($p).'</p>', $draft->paragraphs))
            .'<p><strong>'.e($draft->senderName).'</strong></p>';

        return $this->drafts->handle(new CreateArticleDraftCommand($actorId, ArticleOrigin::Staff, new ArticleInput(
            title: $draft->title,
            body: $body,
            sourceName: self::SECTION,
            categoryId: $this->category(),
            authorIds: [$this->author()],
            kicker: self::SECTION,
        )));
    }

    private function category(): int
    {
        $slug = Slug::fromText(self::SECTION);
        $id = CategoryModel::query()->where('slug', $slug->value)->value('id');

        return is_numeric($id) ? (int) $id : $this->categories->create(new CategoryData(self::SECTION, $slug, sortOrder: 100, icon: 'mail'))->value;
    }

    private function author(): int
    {
        $slug = Slug::fromText(self::SECTION);
        $id = AuthorModel::query()->where('slug', $slug->value)->value('id');

        return is_numeric($id) ? (int) $id : $this->authors->create(new AuthorData(self::SECTION, $slug))->value;
    }
}
