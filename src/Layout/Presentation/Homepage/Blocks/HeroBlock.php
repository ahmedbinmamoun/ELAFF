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
 * Lead story + secondary stories (FR-HOME-02 "hero", FR-ART-07).
 */
final readonly class HeroBlock implements ConfigurableBlock, HomepageBlockRenderer
{
    public function __construct(private ArticleQueries $articles) {}

    public function type(): string
    {
        return 'hero';
    }

    public function label(): string
    {
        return BlockForm::label($this->type());
    }

    public function optionsForm(): array
    {
        return [BlockForm::number('secondary', 3, 0, 8)];
    }

    public function render(array $options, HomepageContext $context): ?View
    {
        $cards = $this->articles->hero(1 + BlockOptions::int($options, 'secondary', 3, 8));

        if ($cards === []) {
            return null;
        }

        $context->markShown(...array_map(static fn (ArticleCard $c): int => $c->id, $cards));

        return view('homepage.blocks.hero', ['lead' => $cards[0], 'secondary' => array_slice($cards, 1)]);
    }
}
