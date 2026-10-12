<?php

declare(strict_types=1);

use Database\Seeders\CategorySeeder;
use Database\Seeders\HomepageBlocksSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Identity\Application\Role;
use Elaf\Layout\Application\Homepage\AddBlock\AddHomepageBlockCommand;
use Elaf\Layout\Application\Homepage\AddBlock\AddHomepageBlockHandler;
use Elaf\Layout\Application\Homepage\HomepageBlockInput;
use Elaf\Layout\Infrastructure\Persistence\HomepageBlockModel;
use Elaf\Layout\Presentation\Filament\Homepage\Pages\CreateHomepageBlock;
use Elaf\Layout\Presentation\Filament\Homepage\Pages\ListHomepageBlocks;
use Elaf\Layout\Presentation\Homepage\HomepageBlockRegistry;
use Elaf\Layout\Presentation\Homepage\HomepageBlockRenderer;
use Elaf\Layout\Presentation\Homepage\HomepageContext;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\AdminFixtures;
use Tests\Support\PublicFixtures;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, CategorySeeder::class, HomepageBlocksSeeder::class]);
    Filament::setCurrentPanel('admin');
    $this->fx = new PublicFixtures;
    $this->sudan = CategoryModel::query()->where('slug', 'أخبار-السودان')->firstOrFail();
    $this->economy = CategoryModel::query()->where('slug', 'الاقتصاد')->firstOrFail();
    $author = AuthorModel::query()->create(['name' => 'كاتب', 'slug' => 'katib'])->id;
    $this->fx->published('خبر عاجل للاختبار', $this->sudan->id, [$author],
        then: static fn ($a, $clock, $by) => $a->markBreaking(null, $by, $clock));
    $this->fx->published('مقال في الاقتصاد', $this->economy->id, [$author]);
    $this->chief = AdminFixtures::user(Role::EditorInChief);
    $this->home = function () {
        $this->app->forgetScopedInstances();

        return $this->get('/')->assertOk();
    };
});

it('seeds the default composition as editable blocks, once', function (): void {
    $this->seed(HomepageBlocksSeeder::class);

    expect(HomepageBlockModel::query()->orderBy('position')->pluck('type')->all())
        ->toBe(['breaking_ticker', 'hero', 'latest_articles', 'most_read', 'current_issue', 'columnists', 'category_section']);
    ($this->home)()->assertSee('x-data="ticker(', false);
});

it('keeps the homepage screen for the Editor-in-Chief and Admin (PRD §2)', function (): void {
    $this->actingAs(AdminFixtures::user(Role::Editor))->get('/admin/homepage-blocks')->assertForbidden();
    $this->actingAs($this->chief)->get('/admin/homepage-blocks')->assertOk()->assertSee('شريط الأخبار العاجلة');
});

it('adds a configured block from the admin (FR-HOME-01, 02)', function (): void {
    $this->actingAs($this->chief);
    HomepageBlockModel::query()->where('type', 'category_section')->delete();

    Livewire::test(CreateHomepageBlock::class)
        ->fillForm(['type' => 'category_section', 'region' => 'bottom', 'is_active' => true])
        ->fillForm(['options' => ['category' => 'الاقتصاد', 'count' => 2]])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(HomepageBlockModel::query()->where('type', 'category_section')->value('config'))->toEqual(['category' => 'الاقتصاد', 'count' => 2]);
    ($this->home)()->assertSee(route('category.show', 'الاقتصاد'), false);
});

it('reorders by drag and drop through the use case and hides blocks', function (): void {
    $this->actingAs($this->chief);
    $ids = HomepageBlockModel::query()->orderBy('position')->pluck('id')->all();
    $ticker = HomepageBlockModel::query()->where('type', 'breaking_ticker')->firstOrFail();

    Livewire::test(ListHomepageBlocks::class)->call('reorderTable', array_reverse(array_map(strval(...), $ids)));
    expect(HomepageBlockModel::query()->orderBy('position')->pluck('id')->all())->toBe(array_reverse($ids));

    Livewire::test(ListHomepageBlocks::class)->callAction(TestAction::make('toggle')->table($ticker));
    expect($ticker->refresh()->is_active)->toBeFalse();
    ($this->home)()->assertDontSee('x-data="ticker(', false);

    // Other pages keep their ticker under the header.
    $this->get(route('category.show', 'الاقتصاد'))->assertSee('x-data="ticker(', false);
});

it('ignores a stale drag (blocks added meanwhile)', function (): void {
    $this->actingAs($this->chief);
    $ids = HomepageBlockModel::query()->orderBy('position')->pluck('id')->all();

    Livewire::test(ListHomepageBlocks::class)->call('reorderTable', array_map(strval(...), array_slice($ids, 1)));

    expect(HomepageBlockModel::query()->orderBy('position')->pluck('id')->all())->toBe($ids);
});

it('takes a new block type with one class and one registration (FR-HOME-03)', function (): void {
    $this->app->make(HomepageBlockRegistry::class)->register(new class implements HomepageBlockRenderer
    {
        public function type(): string
        {
            return 'promo';
        }

        public function render(array $options, HomepageContext $context): ?View
        {
            return view()->file(base_path('tests/Support/Views/promo.blade.php'), ['text' => $options['text'] ?? '']);
        }
    });
    $this->actingAs($this->chief);

    app(AddHomepageBlockHandler::class)->handle(new AddHomepageBlockCommand($this->chief->id, new HomepageBlockInput('promo', 'bottom', ['text' => 'كتلة جديدة'])));

    ($this->home)()->assertSee('كتلة جديدة');
    $this->get('/admin/homepage-blocks')->assertOk()->assertSee('promo');
});
