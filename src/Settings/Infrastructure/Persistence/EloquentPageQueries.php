<?php

declare(strict_types=1);

namespace Elaf\Settings\Infrastructure\Persistence;

use DateTimeImmutable;
use Elaf\Settings\Application\Page\Query\PageQueries;
use Elaf\Settings\Application\Page\Query\PageView;

final class EloquentPageQueries implements PageQueries
{
    public function published(string $slug): ?PageView
    {
        $page = PageModel::query()->where('slug', $slug)->where('is_published', true)->first();

        return $page === null ? null : new PageView(
            $page->id, $page->title, $page->slug, $page->body, $page->seo_title, $page->seo_description,
            $page->updated_at?->toDateTimeImmutable() ?? new DateTimeImmutable,
        );
    }
}
