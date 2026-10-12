<?php

declare(strict_types=1);

use Elaf\Layout\Application\Homepage\AddBlock\AddHomepageBlockCommand;
use Elaf\Layout\Application\Homepage\AddBlock\AddHomepageBlockHandler;
use Elaf\Layout\Application\Homepage\ConfigureBlock\ConfigureHomepageBlockCommand;
use Elaf\Layout\Application\Homepage\ConfigureBlock\ConfigureHomepageBlockHandler;
use Elaf\Layout\Application\Homepage\HomepageBlock;
use Elaf\Layout\Application\Homepage\HomepageBlockInput as Input;
use Elaf\Layout\Application\Homepage\HomepageBlocks;
use Elaf\Layout\Application\Homepage\RemoveBlock\RemoveHomepageBlockCommand;
use Elaf\Layout\Application\Homepage\RemoveBlock\RemoveHomepageBlockHandler;
use Elaf\Layout\Application\Homepage\ReorderBlocks\ReorderHomepageBlocksCommand;
use Elaf\Layout\Application\Homepage\ReorderBlocks\ReorderHomepageBlocksHandler;
use Elaf\Layout\Application\LayoutPermission;
use Elaf\Layout\Domain\Homepage\BlockRegion;
use Elaf\Layout\Domain\Homepage\Events\HomepageLayoutChanged;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Application\Validation\RecordNotFound;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAuthorizer;
use Tests\Support\Fakes\InMemoryHomepageBlocks;
use Tests\Support\Fakes\RecordingEventDispatcher;

beforeEach(function (): void {
    $this->blocks = new InMemoryHomepageBlocks;
    $this->authorizer = new InMemoryAuthorizer([LayoutPermission::ManageHomepage->value]);
    $this->events = new RecordingEventDispatcher;
    $homepage = new HomepageBlocks($this->blocks, $this->blocks, $this->authorizer, new ImmediateTransaction, $this->events, new FrozenClock);
    $this->add = fn (Input $i) => (new AddHomepageBlockHandler($homepage))->handle(new AddHomepageBlockCommand(1, $i));
    $this->configure = fn (int $id, Input $i) => (new ConfigureHomepageBlockHandler($homepage))->handle(new ConfigureHomepageBlockCommand(1, $id, $i));
    $this->reorder = fn (array $ids) => (new ReorderHomepageBlocksHandler($homepage))->handle(new ReorderHomepageBlocksCommand(1, $ids));
    $this->remove = fn (int $id) => (new RemoveHomepageBlockHandler($homepage))->handle(new RemoveHomepageBlockCommand(1, $id));
    $this->order = fn (): array => array_map(static fn (HomepageBlock $b): string => $b->data->type, $this->blocks->all());
    $this->reason = static function (callable $work): string {
        try {
            $work();
        } catch (InvalidInput $e) {
            return $e->field.':'.$e->reason;
        }

        return 'none';
    };
});

it('adds, configures, reorders and removes blocks, announcing each change (FR-HOME-01)', function (): void {
    $hero = ($this->add)(new Input('hero', 'top', ['secondary' => '3', 'blank' => '']));
    $latest = ($this->add)(new Input('latest_articles', 'main'));
    $read = ($this->add)(new Input('most_read', 'aside', ['limit' => 5], false));

    expect(($this->order)())->toBe(['hero', 'latest_articles', 'most_read'])
        ->and($this->blocks->find($hero)?->data->options)->toBe(['secondary' => '3'])
        ->and($this->blocks->find($read)?->data->isActive)->toBeFalse();

    ($this->configure)($latest, new Input('latest_articles', 'bottom', ['strip' => 4]));
    expect($this->blocks->find($latest)?->data->region)->toBe(BlockRegion::Bottom);

    ($this->reorder)([$read, $hero, $latest]);
    expect(($this->order)())->toBe(['most_read', 'hero', 'latest_articles']);

    ($this->remove)($read);
    expect(($this->order)())->toBe(['hero', 'latest_articles'])
        ->and($this->events->ofType(HomepageLayoutChanged::class))->toHaveCount(6);
});

it('checks type, region and options', function (Input $input, string $expected): void {
    expect(($this->reason)(fn () => ($this->add)($input)))->toBe($expected);
})->with([
    'unknown type' => [new Input('video_gallery', 'main'), 'type:unknown_block_type'],
    'unknown region' => [new Input('hero', 'sidebar'), 'region:unknown_region'],
    'nested option' => [new Input('hero', 'main', ['secondary' => [1, 2]]), 'options:invalid_block_option'],
    'bad option name' => [new Input('hero', 'main', ['Secondary Count' => 3]), 'options:invalid_block_option'],
    'long option' => [new Input('hero', 'main', ['note' => str_repeat('x', 201)]), 'options:invalid_block_option'],
]);

it('refuses an order that does not name every block once', function (): void {
    $a = ($this->add)(new Input('hero', 'top'));
    $b = ($this->add)(new Input('most_read', 'aside'));

    expect(($this->reason)(fn () => ($this->reorder)([$a])))->toBe('blocks:stale_block_order')
        ->and(($this->reason)(fn () => ($this->reorder)([$a, $a])))->toBe('blocks:stale_block_order')
        ->and(($this->reason)(fn () => ($this->reorder)([$b, $a, 99])))->toBe('blocks:stale_block_order');
});

it('needs the homepage permission and existing blocks', function (): void {
    expect(fn () => ($this->configure)(42, new Input('hero', 'top')))->toThrow(RecordNotFound::class)
        ->and(fn () => ($this->remove)(42))->toThrow(RecordNotFound::class);

    $this->authorizer->revoke(LayoutPermission::ManageHomepage->value);
    expect(fn () => ($this->add)(new Input('hero', 'top')))->toThrow(NotAuthorized::class)
        ->and(fn () => ($this->reorder)([]))->toThrow(NotAuthorized::class);
});
