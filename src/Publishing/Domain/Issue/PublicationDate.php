<?php

declare(strict_types=1);

namespace Elaf\Publishing\Domain\Issue;

use DateTimeImmutable;
use Elaf\Publishing\Domain\Issue\Exception\InvalidIssue;

/**
 * A calendar day, without time or time zone.
 */
final readonly class PublicationDate
{
    private function __construct(public string $value) {}

    /**
     * @param  string  $date  Y-m-d
     */
    public static function fromString(string $date): self
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw InvalidIssue::because('invalid_date', sprintf('"%s" is not a Y-m-d date.', $date));
        }

        return new self($date);
    }

    public static function fromDateTime(DateTimeImmutable $moment): self
    {
        return new self($moment->format('Y-m-d'));
    }

    public function toDateTime(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->value);
    }

    public function year(): int
    {
        return (int) substr($this->value, 0, 4);
    }

    public function equals(self $other): bool
    {
        return $other->value === $this->value;
    }
}
