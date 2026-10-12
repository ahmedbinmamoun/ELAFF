<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Homepage;

use Filament\Schemas\Components\Component;

/**
 * A block type that describes itself to the admin: its name and the form
 * for its options (fields named "options.<key>"). Optional: a renderer
 * without it is listed by its type key with no options.
 */
interface ConfigurableBlock
{
    public function label(): string;

    /**
     * @return list<Component|\Filament\Forms\Components\Field>
     */
    public function optionsForm(): array;
}
