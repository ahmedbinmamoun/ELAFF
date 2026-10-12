<?php

declare(strict_types=1);

namespace Elaf\Engagement\Application\Letter\Convert;

final readonly class LetterDraft
{
    /**
     * @param  list<string>  $paragraphs  plain text
     */
    public function __construct(
        public string $title,
        public array $paragraphs,
        public string $senderName,
    ) {}
}
