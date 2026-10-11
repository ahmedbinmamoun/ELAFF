<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Http\Controllers;

use Elaf\Content\Application\Query\ArticleCard;
use Elaf\Content\Application\Query\ArticleQueries;
use Elaf\Content\Application\Query\CategoryRef;
use Elaf\Content\Application\Query\TaxonomyQueries;
use Elaf\Shared\Domain\Time\Clock;
use Illuminate\Contracts\View\View;

/**
 * Homepage (PRD §3.5, fixed composition until the block manager of Phase 5).
 */
final readonly class HomeController
{
    private const HERO = 5;

    private const LATEST = 8;

    private const SECTIONS = 6;

    private const PER_SECTION = 4;

    public function __construct(
        private ArticleQueries $articles,
        private TaxonomyQueries $taxonomy,
        private Clock $clock,
    ) {}

    public function __invoke(): View
    {
        $hero = $this->articles->hero(self::HERO);
        $categories = array_slice($this->taxonomy->visibleCategories(), 0, self::SECTIONS);

        return view('content.home', [
            'lead' => $hero[0] ?? null,
            'secondary' => array_slice($hero, 1),
            'latest' => $this->articles->latest(self::LATEST, array_map(static fn (ArticleCard $c): int => $c->id, $hero)),
            'sections' => $this->articles->categorySections(array_map(static fn (CategoryRef $c): int => $c->id, $categories), self::PER_SECTION),
            'columnists' => $this->articles->columnists(6),
            'breaking' => $this->articles->breaking($this->clock->now(), 10),
        ]);
    }
}
