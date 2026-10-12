<?php

declare(strict_types=1);

namespace Elaf\Engagement\Application\Letter\Convert;

use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Engagement\Application\Letter\LetterModeration;
use Elaf\Shared\Domain\Time\Clock;
use LogicException;

/**
 * FR-LET-02: an approved letter becomes a draft article, which then goes
 * through the normal editorial workflow (edit, approve, publish).
 */
final readonly class ConvertLetterToArticleHandler
{
    public function __construct(
        private LetterModeration $letters,
        private LetterArticles $articles,
        private Clock $clock,
    ) {}

    public function handle(ConvertLetterToArticleCommand $command): ArticleId
    {
        $this->letters->requireModerator();
        $letter = $this->letters->load($command->letterId);
        $letter->requireConvertible();
        $paragraphs = array_values(array_filter(array_map(trim(...), preg_split('/\n\s*\n|\n/', $letter->body()) ?: []), static fn (string $p): bool => $p !== ''));
        $articleId = null;

        $this->letters->persist($letter, function () use ($letter, $paragraphs, $command, &$articleId): void {
            $articleId = $this->articles->createDraft(new LetterDraft($letter->title(), $paragraphs, $letter->sender()->name), $command->actorId);
            $letter->markConverted($articleId, $command->actorId, $this->clock);
        });

        return $articleId ?? throw new LogicException('The article was not created.');
    }
}
