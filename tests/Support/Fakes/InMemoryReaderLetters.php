<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Engagement\Application\Letter\Convert\LetterArticles;
use Elaf\Engagement\Application\Letter\Convert\LetterDraft;
use Elaf\Engagement\Domain\Letter\Exception\LetterNotFound;
use Elaf\Engagement\Domain\Letter\ReaderLetter;
use Elaf\Engagement\Domain\Letter\ReaderLetterId;
use Elaf\Engagement\Domain\Letter\ReaderLetterRepository;

/**
 * Letters, plus the drafts "created" from them.
 */
final class InMemoryReaderLetters implements LetterArticles, ReaderLetterRepository
{
    /** @var array<int, ReaderLetter> */
    private array $letters = [];

    /** @var list<array{draft: LetterDraft, actor: int}> */
    public array $drafts = [];

    private int $lastId = 0;

    public function nextId(): ReaderLetterId
    {
        return ReaderLetterId::fromInt(++$this->lastId);
    }

    public function get(ReaderLetterId $id): ReaderLetter
    {
        return $this->letters[$id->value] ?? throw LetterNotFound::withId($id);
    }

    public function save(ReaderLetter $letter): void
    {
        $copy = clone $letter;
        $copy->releaseEvents();
        $this->letters[$letter->id()->value] = $copy;
    }

    public function createDraft(LetterDraft $draft, int $actorId): ArticleId
    {
        $this->drafts[] = ['draft' => $draft, 'actor' => $actorId];

        return ArticleId::fromInt(500 + count($this->drafts));
    }
}
