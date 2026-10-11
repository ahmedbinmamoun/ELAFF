<?php

declare(strict_types=1);

namespace Tests\Support\Contract;

use App\Models\User;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Content\Domain\Series\SeriesId;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Content\Infrastructure\Persistence\Models\SeriesModel;
use Elaf\Content\Infrastructure\Persistence\Models\TagModel;
use Elaf\Identity\Domain\UserId;

final class EloquentContentFixtures implements ContentFixtures
{
    private int $next = 0;

    public function user(): UserId
    {
        return UserId::fromInt(User::factory()->create()->id);
    }

    public function category(): CategoryId
    {
        $n = ++$this->next;

        return CategoryId::fromInt(CategoryModel::query()->create(['name' => "قسم {$n}", 'slug' => "category-{$n}"])->id);
    }

    public function author(): AuthorId
    {
        $n = ++$this->next;

        return AuthorId::fromInt(AuthorModel::query()->create(['name' => "كاتب {$n}", 'slug' => "author-{$n}"])->id);
    }

    public function tag(): TagId
    {
        $n = ++$this->next;

        return TagId::fromInt(TagModel::query()->create(['name' => "وسم {$n}", 'slug' => "tag-{$n}"])->id);
    }

    public function series(): SeriesId
    {
        $n = ++$this->next;

        return SeriesId::fromInt(SeriesModel::query()->create(['title' => "سلسلة {$n}", 'slug' => "series-{$n}"])->id);
    }
}
