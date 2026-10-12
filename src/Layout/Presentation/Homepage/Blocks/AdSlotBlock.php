<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Homepage\Blocks;

use Elaf\Layout\Domain\Ad\AdSlot;
use Elaf\Layout\Presentation\Ads\AdSlots;
use Elaf\Layout\Presentation\Homepage\ConfigurableBlock;
use Elaf\Layout\Presentation\Homepage\HomepageBlockRenderer;
use Elaf\Layout\Presentation\Homepage\HomepageContext;
use Filament\Forms\Components\Select;
use Illuminate\Contracts\View\View;

/**
 * An ad between homepage sections (FR-HOME-02 "ad_slot", FR-ADS-01).
 * Option "slot", default home_between_sections. Nothing when no ad runs.
 */
final readonly class AdSlotBlock implements ConfigurableBlock, HomepageBlockRenderer
{
    public function __construct(private AdSlots $ads) {}

    public function type(): string
    {
        return 'ad_slot';
    }

    public function label(): string
    {
        return BlockForm::label($this->type());
    }

    public function optionsForm(): array
    {
        return [
            Select::make('options.slot')
                ->label(__('admin.homepage.options.slot'))
                ->placeholder(__('admin.ad.slots.'.AdSlot::HomeBetweenSections->value))
                ->options(array_combine(
                    array_map(static fn (AdSlot $s): string => $s->value, AdSlot::cases()),
                    array_map(static fn (AdSlot $s): string => (string) __('admin.ad.slots.'.$s->value), AdSlot::cases()),
                )),
        ];
    }

    public function render(array $options, HomepageContext $context): ?View
    {
        $slot = AdSlot::tryFrom(is_string($options['slot'] ?? null) ? $options['slot'] : '') ?? AdSlot::HomeBetweenSections;

        return $this->ads->pick($slot) === null ? null : view('homepage.blocks.ad-slot', ['place' => $slot->value]);
    }
}
