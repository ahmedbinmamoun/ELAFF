<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application;

enum ContributorPermission: string
{
    /** Approve, reject, suspend, reinstate contributors (Editor and higher). */
    case ManageContributors = 'contributors.manage';
}
