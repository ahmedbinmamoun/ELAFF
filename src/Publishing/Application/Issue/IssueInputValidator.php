<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Issue;

use Elaf\Publishing\Domain\Issue\Exception\InvalidIssue;
use Elaf\Publishing\Domain\Issue\Issue;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Publishing\Domain\Issue\IssueNumber;
use Elaf\Publishing\Domain\Issue\IssueRepository;
use Elaf\Publishing\Domain\Issue\PublicationDate;
use Elaf\Shared\Application\Validation\Fields;
use Elaf\Shared\Application\Validation\InvalidInput;

/**
 * Field errors for the issue form (number, published_on, title).
 */
final readonly class IssueInputValidator
{
    public function __construct(private IssueRepository $issues) {}

    /**
     * @param  IssueId|null  $self  the issue being updated, null on upload
     */
    public function validate(IssueInput $input, ?IssueId $self = null): IssueDetails
    {
        try {
            $number = new IssueNumber($input->number);
        } catch (InvalidIssue $e) {
            throw InvalidInput::because('number', $e->reason, $e->getMessage());
        }

        $existing = $this->issues->findByNumber($number);

        if ($existing !== null && ($self === null || ! $existing->id()->equals($self))) {
            throw InvalidInput::because('number', 'issue_number_taken', sprintf('Issue %d already exists.', $number->value), ['number' => $number->value]);
        }

        try {
            $publishedOn = PublicationDate::fromString(trim($input->publishedOn));
        } catch (InvalidIssue $e) {
            throw InvalidInput::because('published_on', $e->reason, $e->getMessage());
        }

        return new IssueDetails($number, $publishedOn, Fields::optional('title', $input->title, Issue::MAX_TITLE_LENGTH));
    }
}
