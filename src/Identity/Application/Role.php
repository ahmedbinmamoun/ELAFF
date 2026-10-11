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
}
