<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application;

enum IssuePermission: string
{
    /** Upload, edit, reprocess and delete print issues (Editor-in-Chief and Admin, PRD §2). */
    case ManageIssues = 'issues.manage';
}
