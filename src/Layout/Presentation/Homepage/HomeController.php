<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Homepage;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;

final readonly class HomeController
{
    public function __construct(
        private HomepageLayout $layout,
        private Repository $config,
    ) {}

    public function __invoke(): View
    {
        $blocks = $this->config->get('homepage.blocks', []);

        return view('homepage.index', [
            'regions' => $this->layout->render(is_array($blocks) ? array_values(array_filter($blocks, is_array(...))) : []),
        ]);
    }
}
