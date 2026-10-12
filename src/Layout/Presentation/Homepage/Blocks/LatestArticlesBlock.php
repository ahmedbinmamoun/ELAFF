<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Homepage\Blocks;

use Elaf\Content\Application\Query\ArticleCard;
use Elaf\Content\Application\Query\ArticleQueries;
use Elaf\Layout\Presentation\Homepage\ConfigurableBlock;
use Elaf\Layout\Presentation\Homepage\HomepageBlockRenderer;
use Elaf\Layout\Presentation\Homepage\HomepageContext;
use Illuminate\Contracts\View\View;

/**
 * "آخر الأخبار": a few featured cards and a scrollable strip (FR-HOME-02 "latest_articles").
 */
final readonly class LatestArticlesBlock implements ConfigurableBlock, HomepageBlockRenderer
{
    public function __construct(private ArticleQueries $articles) {}

    public function type(): string
    {
        return 'latest_articles';
    }

    public function label(): string
    {
        return BlockForm::label($this->type());
    }

    public function optionsForm(): array
    {
        return [BlockForm::number('featured', 2, 0, 6), BlockForm::number('strip', 8, 0, 20)];
    }

    public function render(array $options, HomepageContext $context): ?View
    {
        $featured = BlockOptions::int($options, 'featured', 2, 6);
        $cards = $this->articles->latest($featured + BlockOptions::int($options, 'strip', 8, 20), $context->shown());

        if ($cards === []) {
            return null;
        }

        $context->markShown(...array_map(static fn (ArticleCard $c): int => $c->id, $cards));

        return view('homepage.blocks.latest', ['featured' => array_slice($cards, 0, $featured), 'strip' => array_slice($cards, $featured)]);
    }
}
