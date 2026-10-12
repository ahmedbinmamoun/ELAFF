<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Issue;

use Elaf\Publishing\Application\IssuePermission;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;

final readonly class IssueAccess
{
    public function __construct(private Authorizer $authorizer) {}

    public function requireManage(): void
    {
        if (! $this->authorizer->allows(IssuePermission::ManageIssues->value)) {
            throw NotAuthorized::lacks(IssuePermission::ManageIssues->value);
        }
    }
}
