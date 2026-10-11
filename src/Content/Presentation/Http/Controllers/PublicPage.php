<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Http\Controllers;

use Elaf\Content\Application\Query\Paginated;
use Illuminate\Http\Request;

/**
 * Small helpers shared by the public controllers.
 */
final class PublicPage
{
    public const PER_PAGE = 20;

    public const MOST_READ = 5;

    public static function page(Request $request): int
    {
        $page = $request->query('page');

        return is_numeric($page) ? max(1, (int) $page) : 1;
    }

    /**
     * Page numbers past the end are not pages.
     *
     * @template T
     *
     * @param  Paginated<T>  $result
     */
    public static function abortPastEnd(Paginated $result): void
    {
        abort_if($result->page > 1 && $result->page > $result->lastPage(), 404);
    }
}
