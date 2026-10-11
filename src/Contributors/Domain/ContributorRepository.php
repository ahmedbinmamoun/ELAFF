<?php

declare(strict_types=1);

namespace Elaf\Contributors\Domain;

use Elaf\Contributors\Domain\Exception\ContributorNotFound;
use Elaf\Identity\Domain\UserId;

interface ContributorRepository
{
    /**
     * @throws ContributorNotFound
     */
    public function get(UserId $id): Contributor;

    /**
     * The account row must exist already (see ContributorAccounts).
     */
    public function save(Contributor $contributor): void;
}
