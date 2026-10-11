<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Query;

final readonly class SeriesPage
{
    /**
     * @param  list<ArticleCard>  $parts  in part order
     */
    public function __construct(
        public string $title,
        public string $slug,
        public ?string $description,
        public array $parts,
    ) {}
}
