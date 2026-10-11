<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Homepage\Blocks;

use Elaf\Content\Application\Query\ArticleQueries;
use Elaf\Layout\Presentation\Homepage\HomepageBlockRenderer;
use Elaf\Layout\Presentation\Homepage\HomepageContext;
use Illuminate\Contracts\View\View;

/**
 * Columnists with photo and latest column (FR-HOME-02 "columnists", FR-AUT-02).
 */
final readonly class ColumnistsBlock implements HomepageBlockRenderer
{
    public function __construct(private ArticleQueries $articles) {}

    public function type(): string
    {
        return 'columnists';
    }

    public function render(array $options, HomepageContext $context): ?View
    {
        $items = $this->articles->columnists(BlockOptions::int($options, 'limit', 6, 12));

        return $items === [] ? null : view('homepage.blocks.columnists', ['items' => $items]);
    }
}
