<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Page;

use Elaf\Settings\Application\SettingsPermission;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;

final readonly class PageAccess
{
    public function __construct(private Authorizer $authorizer) {}

    public function require(): void
    {
        if (! $this->authorizer->allows(SettingsPermission::ManagePages->value)) {
            throw NotAuthorized::lacks(SettingsPermission::ManagePages->value);
        }
    }
}
