<?php

declare(strict_types=1);

namespace Elaf\Shared\Application\Html;

enum SanitizerProfile: string
{
    /** Newsroom staff: rich text incl. tables, figures and CSS classes. */
    case Staff = 'staff';

    /** External contributors (PRD §16): same structure, no classes/ids, links get rel=nofollow. */
    case Contributor = 'contributor';
}
