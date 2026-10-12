<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Homepage\Blocks;

use Elaf\Content\Application\Query\ArticleQueries;
use Elaf\Layout\Presentation\Homepage\ConfigurableBlock;
use Elaf\Layout\Presentation\Homepage\HomepageBlockRenderer;
use Elaf\Layout\Presentation\Homepage\HomepageContext;
use Elaf\Shared\Domain\Time\Clock;
use Illuminate\Contracts\View\View;

/**
 * "الأكثر قراءة" numbered list (FR-HOME-02 "most_read"). May repeat articles
 * shown above: it is a ranking, not a feed.
 */
final readonly class MostReadBlock implements ConfigurableBlock, HomepageBlockRenderer
{
    public function __construct(
        private ArticleQueries $articles,
        private Clock $clock,
    ) {}

    public function type(): string
    {
        return 'most_read';
    }

    public function label(): string
    {
        return BlockForm::label($this->type());
    }

    public function optionsForm(): array
    {
        return [BlockForm::number('limit', 5, 1, 10), BlockForm::number('days', 30, 0, 365)->helperText(__('admin.homepage.hints.days'))];
    }

    public function render(array $options, HomepageContext $context): ?View
    {
        $days = BlockOptions::int($options, 'days', 30, 365);
        $cards = $this->articles->mostRead(BlockOptions::int($options, 'limit', 5, 10), $days > 0 ? $this->clock->now()->modify("-{$days} days") : null);

        return $cards === [] ? null : view('homepage.blocks.most-read', ['cards' => $cards]);
    }
}
