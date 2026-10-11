<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Homepage;

use Illuminate\Contracts\View\View;

/**
 * One homepage block type (FR-HOME-02). A new type = a new class registered
 * in LayoutPresentationServiceProvider; nothing else changes (FR-HOME-03).
 */
interface HomepageBlockRenderer
{
    public function type(): string;

    /**
     * @param  array<string, mixed>  $options  from the block configuration
     * @return View|null null when there is nothing to show
     */
    public function render(array $options, HomepageContext $context): ?View;
}
