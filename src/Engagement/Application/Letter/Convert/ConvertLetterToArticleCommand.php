<?php

declare(strict_types=1);

namespace Elaf\Engagement\Application\Letter\Convert;

final readonly class ConvertLetterToArticleCommand
{
    public function __construct(
        public int $actorId,
        public int $letterId,
    ) {}
}
