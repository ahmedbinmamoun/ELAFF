<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Homepage;

/**
 * Block renderers by type; no switch on type strings anywhere (FR-HOME-03).
 */
final class HomepageBlockRegistry
{
    /** @var array<string, HomepageBlockRenderer> */
    private array $renderers = [];

    /**
     * @param  iterable<HomepageBlockRenderer>  $renderers
     */
    public function __construct(iterable $renderers = [])
    {
        foreach ($renderers as $renderer) {
            $this->register($renderer);
        }
    }

    public function register(HomepageBlockRenderer $renderer): void
    {
        $this->renderers[$renderer->type()] = $renderer;
    }

    public function get(string $type): ?HomepageBlockRenderer
    {
        return $this->renderers[$type] ?? null;
    }

    /**
     * @return list<string>
     */
    public function types(): array
    {
        return array_keys($this->renderers);
    }
}
