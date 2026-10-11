<?php

declare(strict_types=1);

use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Contributors\Application\ContributorAccounts;
use Elaf\Contributors\Domain\Contributor;
use Elaf\Contributors\Domain\ContributorProfile;
use Elaf\Contributors\Domain\ContributorRepository;
use Elaf\Contributors\Domain\DisplayName;
use Elaf\Contributors\Domain\Exception\ContributorNotFound;
use Elaf\Contributors\Infrastructure\Persistence\EloquentContributorAccounts;
use Elaf\Contributors\Infrastructure\Persistence\EloquentContributorRepository;
use Elaf\Identity\Application\Role;
use Elaf\Identity\Application\RoleAssignments;
use Elaf\Identity\Domain\UserId;
use Elaf\Identity\Infrastructure\SpatieRoleAssignments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\InMemoryContributors;
use Tests\Support\Fakes\InMemoryRoleAssignments;

uses(RefreshDatabase::class);

dataset('contributor stores', [
    'in-memory' => fn (): array => [($m = new InMemoryContributors), $m, static fn (): AuthorId => AuthorId::fromInt(5)],
    'eloquent' => fn (): array => [
        new EloquentContributorAccounts,
        app(EloquentContributorRepository::class),
        static fn (): AuthorId => AuthorId::fromInt(AuthorModel::query()->create(['name' => 'ضيف', 'slug' => 'dayf-'.uniqid()])->id),
    ],
]);

it('creates accounts and tells whether an email is taken, case-insensitively', function (ContributorAccounts $accounts, ContributorRepository $repository, Closure $author): void {
    $accounts->create(new DisplayName('آمنة'), 'Amna@Example.com', 'hash');

    expect($accounts->emailExists('amna@example.com'))->toBeTrue()
        ->and($accounts->emailExists('AMNA@EXAMPLE.COM'))->toBeTrue()
        ->and($accounts->emailExists('other@example.com'))->toBeFalse();
})->with('contributor stores');

it('round-trips a contributor through its lifecycle', function (ContributorAccounts $accounts, ContributorRepository $repository, Closure $author): void {
    $clock = new FrozenClock;
    $id = $accounts->create(new DisplayName('آمنة'), 'amna@example.com', 'hash');
    $contributor = Contributor::register($id, new DisplayName('آمنة'), new ContributorProfile('نبذة', '0912345678', 'contributors/a.webp'), $clock);
    $repository->save($contributor);

    $loaded = $repository->get($id);
    expect($loaded->status())->toBe($contributor->status())
        ->and($loaded->profile())->toEqual($contributor->profile())
        ->and($loaded->termsAcceptedAt()->getTimestamp())->toBe($clock->now()->getTimestamp())
        ->and($loaded->releaseEvents())->toBe([]);

    $loaded->verifyEmail($clock);
    $loaded->approve(UserId::fromInt($id->value), $author(), $clock);
    $loaded->requestNameChange(new DisplayName('آمنة ط.'), $clock);
    $repository->save($loaded);

    $again = $repository->get($id);
    expect($again->status())->toBe($loaded->status())
        ->and($again->authorId())->toEqual($loaded->authorId())
        ->and($again->approvedBy())->toEqual($loaded->approvedBy())
        ->and($again->approvedAt()?->getTimestamp())->toBe($loaded->approvedAt()?->getTimestamp())
        ->and($again->requestedName())->toEqual($loaded->requestedName())
        ->and($again->name()->value)->toBe('آمنة');
})->with('contributor stores');

it('throws ContributorNotFound for an unknown id', function (ContributorAccounts $accounts, ContributorRepository $repository, Closure $author): void {
    $repository->get(UserId::fromInt(424242));
})->with('contributor stores')->throws(ContributorNotFound::class);

dataset('role assignments', [
    'in-memory' => fn (): array => [new InMemoryRoleAssignments, static fn (InMemoryRoleAssignments $r, UserId $u): bool => $r->has($u, Role::Contributor)],
    'spatie' => fn (): array => [new SpatieRoleAssignments, static fn (RoleAssignments $r, UserId $u): bool => App\Models\User::query()->findOrFail($u->value)->hasRole('contributor')],
]);

it('assigns and removes roles, removing twice harmlessly', function (RoleAssignments $roles, Closure $has): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = UserId::fromInt(App\Models\User::factory()->create()->id);

    $roles->assign($user, Role::Contributor);
    expect($has($roles, $user))->toBeTrue();

    $roles->remove($user, Role::Contributor);
    $roles->remove($user, Role::Contributor);
    expect($has($roles, $user))->toBeFalse();
})->with('role assignments');
