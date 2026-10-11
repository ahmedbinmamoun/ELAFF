<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Homepage\Blocks;

use Elaf\Layout\Presentation\Homepage\HomepageBlockRenderer;
use Elaf\Layout\Presentation\Homepage\HomepageContext;
use Illuminate\Contracts\View\View;

/**
 * Latest print issue (FR-HOME-02 "current_issue"). Registered now so the
 * homepage configuration is complete; it shows the issue once the issues
 * archive exists (Phase 4, FR-ISS-04).
 */
final readonly class CurrentIssueBlock implements HomepageBlockRenderer
{
    public function type(): string
    {
        return 'current_issue';
    }

    public function render(array $options, HomepageContext $context): ?View
    {
        return null;
    }
}
