<?php

declare(strict_types=1);

namespace Elaf\Engagement\Application;

enum EngagementPermission: string
{
    /** Approve, reject and convert reader letters (Editor and higher, PRD §2). */
    case ModerateLetters = 'letters.moderate';
}
