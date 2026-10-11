<?php

declare(strict_types=1);

namespace Elaf\Identity\Presentation;

use App\Models\User;
use Elaf\Identity\Presentation\Policies\UserPolicy;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\ServiceProvider;

final class IdentityPresentationServiceProvider extends ServiceProvider
{
    public function boot(Gate $gate): void
    {
        $gate->policy(User::class, UserPolicy::class);
    }
}
