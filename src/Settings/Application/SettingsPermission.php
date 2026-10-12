<?php

declare(strict_types=1);

namespace Elaf\Settings\Application;

enum SettingsPermission: string
{
    /** Site identity, contacts, switches (Admin only, PRD §2). */
    case ManageSettings = 'settings.manage';

    /** Static pages: about, contact, advertise, privacy (FR-PAG-01). */
    case ManagePages = 'pages.manage';
}
