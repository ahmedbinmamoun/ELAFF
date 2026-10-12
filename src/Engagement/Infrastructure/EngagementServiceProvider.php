<?php

declare(strict_types=1);

namespace Elaf\Engagement\Infrastructure;

use Elaf\Engagement\Application\Letter\Convert\LetterArticles;
use Elaf\Engagement\Domain\Letter\ReaderLetterRepository;
use Elaf\Engagement\Infrastructure\Content\ContentLetterArticles;
use Elaf\Engagement\Infrastructure\Persistence\EloquentReaderLetterRepository;
use Illuminate\Support\ServiceProvider;

final class EngagementServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        ReaderLetterRepository::class => EloquentReaderLetterRepository::class,
        LetterArticles::class => ContentLetterArticles::class,
    ];
}
