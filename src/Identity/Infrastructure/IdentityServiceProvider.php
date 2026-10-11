<?php

declare(strict_types=1);

namespace Elaf\Identity\Infrastructure;

use Elaf\Identity\Application\RoleAssignments;
use Elaf\Identity\Application\StaffUser\PasswordHasher;
use Elaf\Identity\Application\StaffUser\StaffUserRepository;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        StaffUserRepository::class => EloquentStaffUserRepository::class,
        PasswordHasher::class => LaravelPasswordHasher::class,
        RoleAssignments::class => SpatieRoleAssignments::class,
    ];
}
