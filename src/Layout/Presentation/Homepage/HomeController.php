<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Homepage;

use Elaf\Layout\Application\Homepage\HomepageBlockData;
use Elaf\Layout\Application\Homepage\HomepageBlockQueries;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;

/**
 * The homepage from the stored blocks (FR-HOME-01); config/homepage.php is
 * the composition of a fresh installation, before any block is stored.
 */
final readonly class HomeController
{
    public function __construct(
        private HomepageLayout $layout,
        private HomepageBlockQueries $blocks,
        private Repository $config,
    ) {}

    public function __invoke(): View
    {
        $stored = $this->blocks->active();
        $blocks = $stored === null ? $this->defaults() : array_map(static fn (HomepageBlockData $b): array => [
            'type' => $b->type,
            'region' => $b->region->value,
            'options' => $b->options,
        ], $stored);

        return view('homepage.index', ['regions' => $this->layout->render($blocks)]);
    }

    /**
     * @return list<array<array-key, mixed>>
     */
    private function defaults(): array
    {
        $blocks = $this->config->get('homepage.blocks', []);

        return is_array($blocks) ? array_values(array_filter($blocks, is_array(...))) : [];
    }
}
