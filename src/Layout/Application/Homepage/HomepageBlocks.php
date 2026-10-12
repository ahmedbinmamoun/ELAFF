<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Homepage;

use Elaf\Layout\Application\LayoutPermission;
use Elaf\Layout\Domain\Homepage\BlockRegion;
use Elaf\Layout\Domain\Homepage\Events\HomepageLayoutChanged;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Application\Transaction\Transaction;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Domain\Time\Clock;

/**
 * What every homepage use case shares: the permission, input checks and
 * "change, then announce" (FR-HOME-01).
 */
final readonly class HomepageBlocks
{
    public const MAX_OPTIONS = 20;

    public function __construct(
        public HomepageBlockRepository $blocks,
        private BlockTypeCatalog $types,
        private Authorizer $authorizer,
        private Transaction $transaction,
        private EventDispatcher $events,
        private Clock $clock,
    ) {}

    public function requireManage(): void
    {
        if (! $this->authorizer->allows(LayoutPermission::ManageHomepage->value)) {
            throw NotAuthorized::lacks(LayoutPermission::ManageHomepage->value);
        }
    }

    public function validate(HomepageBlockInput $input): HomepageBlockData
    {
        if (! $this->types->knows($input->type)) {
            throw InvalidInput::because('type', 'unknown_block_type', sprintf('Unknown block type "%s".', $input->type));
        }

        $region = BlockRegion::tryFrom($input->region) ?? throw InvalidInput::because('region', 'unknown_region', 'Unknown region.');

        return new HomepageBlockData($input->type, $region, $this->options($input->options), $input->isActive);
    }

    /**
     * @param  callable(): void  $change
     */
    public function change(int $actorId, callable $change): void
    {
        $this->transaction->run(function () use ($actorId, $change): void {
            $change();
            $this->events->dispatch(new HomepageLayoutChanged($actorId, $this->clock->now()));
        });
    }

    /**
     * Flat scalar options only; blanks dropped so the block's defaults apply.
     *
     * @param  array<array-key, mixed>  $options
     * @return array<string, string|int|bool|null>
     */
    private function options(array $options): array
    {
        $clean = [];

        foreach ($options as $key => $value) {
            if (! is_string($key) || preg_match('/^[a-z_]{1,40}$/', $key) !== 1) {
                throw InvalidInput::because('options', 'invalid_block_option', 'Option names are lowercase words.');
            }

            if ($value === null || $value === '') {
                continue;
            }

            if (! is_scalar($value) || (is_string($value) && mb_strlen($value) > 200)) {
                throw InvalidInput::because('options', 'invalid_block_option', sprintf('Option "%s" is not a short value.', $key));
            }

            $clean[$key] = is_float($value) ? (int) $value : $value;
        }

        if (count($clean) > self::MAX_OPTIONS) {
            throw InvalidInput::because('options', 'invalid_block_option', 'Too many options.');
        }

        return $clean;
    }
}
