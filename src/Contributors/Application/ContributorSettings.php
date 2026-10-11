<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application;

interface ContributorSettings
{
    /** FR-CON-03 `contributors.require_account_approval` (default true). */
    public function requireAccountApproval(): bool;

    /** FR-CON-13: show "كاتب ضيف" in contributor bylines (default true). */
    public function showGuestBylineLabel(): bool;
}
