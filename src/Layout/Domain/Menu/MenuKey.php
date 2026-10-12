<?php

declare(strict_types=1);

namespace Elaf\Layout\Domain\Menu;

/**
 * The site's menus (FR-NAV-01).
 */
enum MenuKey: string
{
    /** The navy bar under the masthead (and its "more" list). */
    case Header = 'header';

    /** The sections list: drawer and homepage side list, with icons. */
    case Sections = 'sections';

    case Footer = 'footer';
}
