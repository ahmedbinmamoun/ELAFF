<?php

declare(strict_types=1);

namespace Elaf\Engagement\Domain\Letter;

use Elaf\Engagement\Domain\Letter\Exception\LetterNotFound;

interface ReaderLetterRepository
{
    public function nextId(): ReaderLetterId;

    /**
     * @throws LetterNotFound
     */
    public function get(ReaderLetterId $id): ReaderLetter;

    public function save(ReaderLetter $letter): void;
}
