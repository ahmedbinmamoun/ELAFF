<?php

declare(strict_types=1);

namespace Elaf\Engagement\Application\Letter;

use Elaf\Engagement\Application\EngagementPermission;
use Elaf\Engagement\Domain\Letter\ReaderLetter;
use Elaf\Engagement\Domain\Letter\ReaderLetterId;
use Elaf\Engagement\Domain\Letter\ReaderLetterRepository;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Application\Transaction\Transaction;

/**
 * Load → change → save → dispatch for the moderation use cases.
 */
final readonly class LetterModeration
{
    public function __construct(
        private ReaderLetterRepository $letters,
        private Authorizer $authorizer,
        private Transaction $transaction,
        private EventDispatcher $events,
    ) {}

    public function requireModerator(): void
    {
        if (! $this->authorizer->allows(EngagementPermission::ModerateLetters->value)) {
            throw NotAuthorized::lacks(EngagementPermission::ModerateLetters->value);
        }
    }

    public function load(int $id): ReaderLetter
    {
        return $this->letters->get(ReaderLetterId::fromInt($id));
    }

    public function nextId(): ReaderLetterId
    {
        return $this->letters->nextId();
    }

    /**
     * @param  (callable(): void)|null  $before  more work in the same transaction
     */
    public function persist(ReaderLetter $letter, ?callable $before = null): void
    {
        $this->transaction->run(function () use ($letter, $before): void {
            if ($before !== null) {
                $before();
            }

            $this->letters->save($letter);
            $this->events->dispatch(...$letter->releaseEvents());
        });
    }
}
