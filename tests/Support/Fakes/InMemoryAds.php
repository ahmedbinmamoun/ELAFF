<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Layout\Application\Ad\AdClicks;
use Elaf\Layout\Application\Ad\Query\AdCandidate;
use Elaf\Layout\Application\Ad\Query\AdQueries;
use Elaf\Layout\Application\Ad\SelectForSlot\Randomness;
use Elaf\Layout\Domain\Ad\Ad;
use Elaf\Layout\Domain\Ad\AdId;
use Elaf\Layout\Domain\Ad\AdRepository;
use Elaf\Layout\Domain\Ad\Exception\AdNotFound;

/**
 * Ads, their read side, click counter and a scripted "random" source.
 */
final class InMemoryAds implements AdClicks, AdQueries, AdRepository, Randomness
{
    /** @var array<int, Ad> */
    private array $ads = [];

    /** @var array<int, int> */
    public array $clicks = [];

    /** @var list<int> next values returned by upTo(); 1 when empty */
    public array $rolls = [];

    public ?int $lastMax = null;

    private int $lastId = 0;

    public function nextId(): AdId
    {
        return AdId::fromInt(++$this->lastId);
    }

    public function get(AdId $id): Ad
    {
        return $this->ads[$id->value] ?? throw AdNotFound::withId($id);
    }

    public function save(Ad $ad): void
    {
        $copy = clone $ad;
        $copy->releaseEvents();
        $this->ads[$ad->id()->value] = $copy;
    }

    public function delete(AdId $id): void
    {
        unset($this->ads[$id->value]);
    }

    public function active(): array
    {
        $active = [];

        foreach ($this->ads as $ad) {
            if ($ad->isActive()) {
                $active[] = new AdCandidate(
                    $ad->id()->value, $ad->slot()->value, $ad->title(), $ad->creative()->imagePath, $ad->creative()->html,
                    $ad->targetUrl(), $ad->weight(), $ad->schedule()->startsAt, $ad->schedule()->endsAt,
                );
            }
        }

        return $active;
    }

    public function target(int $adId): ?string
    {
        return ($this->ads[$adId] ?? null)?->targetUrl();
    }

    public function record(AdId $id): void
    {
        $this->clicks[$id->value] = ($this->clicks[$id->value] ?? 0) + 1;
    }

    public function upTo(int $max): int
    {
        $this->lastMax = $max;

        return array_shift($this->rolls) ?? 1;
    }
}
