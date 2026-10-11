<?php

declare(strict_types=1);

namespace Elaf\Identity\Application;

/**
 * PRD §2. Role names as stored by Spatie Permission.
 */
enum Role: string
{
    case Writer = 'writer';
    case Contributor = 'contributor';
    case Editor = 'editor';
    case EditorInChief = 'editor-in-chief';
    case Admin = 'admin';

    /**
     * Newsroom roles that use the admin panel; contributors have their own portal.
     *
     * @return list<self>
     */
    public static function staff(): array
    {
        return [self::Writer, self::Editor, self::EditorInChief, self::Admin];
    }

    public function isStaff(): bool
    {
        return in_array($this, self::staff(), true);
    }
}
