<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Homepage\Blocks;

use Elaf\Content\Presentation\Http\PublicChrome;
use Elaf\Layout\Presentation\Homepage\ConfigurableBlock;
use Elaf\Layout\Presentation\Homepage\HomepageBlockRenderer;
use Elaf\Layout\Presentation\Homepage\HomepageContext;
use Illuminate\Contracts\View\View;

/**
 * The breaking news ticker (FR-HOME-02 "breaking_ticker", FR-ART-06). Other
 * pages show it under the header; on the homepage this block decides where,
 * or whether, it appears. Uses the request's header data: no extra query.
 */
final readonly class BreakingTickerBlock implements ConfigurableBlock, HomepageBlockRenderer
{
    public function __construct(private PublicChrome $chrome) {}

    public function type(): string
    {
        return 'breaking_ticker';
    }

    public function label(): string
    {
        return BlockForm::label($this->type());
    }

    public function optionsForm(): array
    {
        return [];
    }

    public function render(array $options, HomepageContext $context): ?View
    {
        $breaking = $this->chrome->data()['breaking'] ?? [];

        return is_array($breaking) && $breaking !== [] ? view('homepage.blocks.ticker', ['breaking' => $breaking]) : null;
    }
}
