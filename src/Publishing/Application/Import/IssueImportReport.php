<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Import;

final readonly class IssueImportReport
{
    /**
     * @param  array<string, int>  $imported  file name => issue number
     * @param  array<string, int>  $skipped  file name => existing issue number
     * @param  array<string, string>  $failed  file name => reason key
     */
    public function __construct(
        public array $imported,
        public array $skipped,
        public array $failed,
    ) {}
}
