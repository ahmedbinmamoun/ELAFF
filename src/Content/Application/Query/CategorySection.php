<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Query;

final readonly class CategorySection
{
    /**
     * @param  list<ArticleCard>  $articles
     */
    public function __construct(
        public CategoryRef $category,
        public array $articles,
    ) {}
}
