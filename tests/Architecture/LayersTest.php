<?php

declare(strict_types=1);

/*
 * PRD §5.9 — the dependency rule, enforced per module.
 *
 * One arch() per target namespace on purpose: with several namespaces in a
 * single expect([...]) Pest 4 silently passes even when a target violates.
 */

const ELAF_MODULES = ['Shared', 'Content', 'Publishing', 'Layout', 'Engagement', 'Identity', 'Contributors', 'Settings', 'Search', 'Seo'];
const FRAMEWORKS = ['Illuminate', 'Laravel', 'Filament', 'Livewire', 'Spatie', 'Carbon'];
const LARAVEL_HELPERS = ['app', 'auth', 'config', 'now', 'today', 'request', 'session', 'cache', 'event', 'dispatch', 'logger', 'resolve', 'env'];

/** @return list<string> */
function elaf_layer(string $layer): array
{
    return array_map(static fn (string $module): string => "Elaf\\{$module}\\{$layer}", ELAF_MODULES);
}

foreach (ELAF_MODULES as $module) {
    $domain = "Elaf\\{$module}\\Domain";
    $application = "Elaf\\{$module}\\Application";
    $infrastructure = "Elaf\\{$module}\\Infrastructure";
    $presentation = "Elaf\\{$module}\\Presentation";

    arch("{$module}: domain is framework-free")
        ->expect($domain)
        ->not->toUse([...FRAMEWORKS, ...LARAVEL_HELPERS]);

    arch("{$module}: domain does not depend on outer layers")
        ->expect($domain)
        ->not->toUse([...elaf_layer('Application'), ...elaf_layer('Infrastructure'), ...elaf_layer('Presentation'), 'App']);

    arch("{$module}: application is framework-free")
        ->expect($application)
        ->not->toUse([...FRAMEWORKS, ...LARAVEL_HELPERS]);

    arch("{$module}: application does not know infrastructure or presentation")
        ->expect($application)
        ->not->toUse([...elaf_layer('Infrastructure'), ...elaf_layer('Presentation'), 'App']);

    arch("{$module}: application classes are final")
        ->expect($application)
        ->classes()
        ->toBeFinal();

    arch("{$module}: infrastructure does not depend on presentation")
        ->expect($infrastructure)
        ->not->toUse(elaf_layer('Presentation'));
}

/*
 * PRD §5.2: Presentation may read Eloquent models, but every write goes
 * through a use case. So no write ports, and no Eloquent repositories.
 */
const WRITE_PORTS = [
    'Elaf\Content\Domain\Article\ArticleRepository',
    'Elaf\Content\Application\Article\ArticleUnitOfWork',
    'Elaf\Content\Application\Article\Revision\ArticleRevisions',
    'Elaf\Content\Application\Category\CategoryRepository',
    'Elaf\Content\Application\Tag\TagRepository',
    'Elaf\Content\Application\Series\SeriesRepository',
    'Elaf\Content\Application\Author\AuthorRepository',
    'Elaf\Identity\Application\StaffUser\StaffUserRepository',
    'Elaf\Identity\Application\RoleAssignments',
    'Elaf\Contributors\Domain\ContributorRepository',
    'Elaf\Contributors\Application\ContributorAccounts',
    'Elaf\Contributors\Application\AuthorProfiles',
    'Elaf\Contributors\Infrastructure\Persistence\EloquentContributorRepository',
    'Elaf\Contributors\Infrastructure\Persistence\EloquentContributorAccounts',
    'Elaf\Content\Infrastructure\Persistence\Eloquent',
    'Elaf\Identity\Infrastructure',
];

foreach (ELAF_MODULES as $module) {
    arch("{$module}: presentation writes only through use cases")
        ->expect("Elaf\\{$module}\\Presentation")
        ->not->toUse(WRITE_PORTS);
}

arch('Elaf source declares strict types')
    ->expect('Elaf')
    ->toUseStrictTypes();

arch('App glue declares strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('no debugging leftovers')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'die'])
    ->not->toBeUsed();
