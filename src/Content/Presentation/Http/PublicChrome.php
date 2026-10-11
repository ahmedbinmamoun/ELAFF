<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Http;

use Elaf\Content\Application\Query\ArticleLink;
use Elaf\Content\Application\Query\ArticleQueries;
use Elaf\Content\Application\Query\CategoryRef;
use Elaf\Content\Application\Query\TaxonomyQueries;
use Elaf\Shared\Domain\Time\Clock;
use Elaf\Shared\Presentation\ArabicDate;
use Illuminate\Contracts\Config\Repository;

/**
 * Data every public page shows (header, ticker, footer), loaded at most once
 * per request however many views ask for it.
 */
final class PublicChrome
{
    /** @var array<string, mixed>|null */
    private ?array $data = null;

    public function __construct(
        private readonly TaxonomyQueries $taxonomy,
        private readonly ArticleQueries $articles,
        private readonly Clock $clock,
        private readonly Repository $config,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data ??= [
            'site' => $this->site(),
            'navigation' => $this->navigation(),
            'breaking' => $this->breaking(),
            'today' => ArabicDate::withWeekday($this->clock->now()),
            'year' => $this->clock->now()->format('Y'),
        ];
    }

    /**
     * @return list<CategoryRef>
     */
    private function navigation(): array
    {
        return $this->taxonomy->visibleCategories();
    }

    /**
     * @return list<ArticleLink>
     */
    private function breaking(): array
    {
        return $this->articles->breaking($this->clock->now(), 10);
    }

    /**
     * @return array<string, mixed>
     */
    private function site(): array
    {
        $site = $this->config->get('elaf.site');

        return is_array($site) ? array_filter($site, is_string(...), ARRAY_FILTER_USE_KEY) : [];
    }
}
