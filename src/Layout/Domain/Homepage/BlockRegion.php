<?php

declare(strict_types=1);

namespace Elaf\Layout\Domain\Homepage;

/**
 * Where a homepage block sits. On mobile the regions stack in this order.
 */
enum BlockRegion: string
{
    /** Full width under the header. */
    case Top = 'top';

    /** Centre column. */
    case Main = 'main';

    /** End column on desktop. */
    case Aside = 'aside';

    /** Full width at the bottom. */
    case Bottom = 'bottom';
}
