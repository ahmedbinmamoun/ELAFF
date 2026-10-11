<?php

declare(strict_types=1);

namespace Elaf\Identity\Application;

enum IdentityPermission: string
{
    case ManageUsers = 'users.manage';
}
