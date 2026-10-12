<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Homepage\Blocks;

use Elaf\Layout\Presentation\Homepage\ConfigurableBlock;
use Elaf\Layout\Presentation\Homepage\HomepageBlockRenderer;
use Elaf\Layout\Presentation\Homepage\HomepageContext;
use Elaf\Publishing\Application\Query\IssueQueries;
use Illuminate\Contracts\View\View;

/**
 * Latest print issue with its cover (FR-HOME-02 "current_issue", FR-ISS-04).
 */
final readonly class CurrentIssueBlock implements ConfigurableBlock, HomepageBlockRenderer
{
    public function __construct(private IssueQueries $issues) {}

    public function type(): string
    {
        return 'current_issue';
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
        $issue = $this->issues->latest();

        return $issue === null ? null : view('homepage.blocks.current-issue', ['issue' => $issue]);
    }
}
