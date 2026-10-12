<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Homepage;

use Elaf\Layout\Application\Homepage\BlockTypeCatalog;

/**
 * Block renderers by type; no switch on type strings anywhere (FR-HOME-03).
 * Also the catalog the use cases check types against.
 */
final class HomepageBlockRegistry implements BlockTypeCatalog
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

    public function knows(string $type): bool
    {
        return isset($this->renderers[$type]);
    }

    /**
     * The admin name of a type; its key when the type does not describe itself.
     */
    public function label(string $type): string
    {
        $renderer = $this->renderers[$type] ?? null;

        return $renderer instanceof ConfigurableBlock ? $renderer->label() : $type;
    }

    /**
     * @return list<\Filament\Schemas\Components\Component|\Filament\Forms\Components\Field>
     */
    public function optionsForm(string $type): array
    {
        $renderer = $this->renderers[$type] ?? null;

        return $renderer instanceof ConfigurableBlock ? $renderer->optionsForm() : [];
    }
}
