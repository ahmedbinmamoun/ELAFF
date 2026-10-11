<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Series\DeleteSeries;

use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Application\Series\SeriesRepository;
use Elaf\Content\Domain\Series\SeriesId;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Application\Validation\RecordNotFound;

/**
 * Only an empty series can go; its articles would otherwise change silently.
 */
final readonly class DeleteSeriesHandler
{
    public function __construct(
        private SeriesRepository $series,
        private Authorizer $authorizer,
    ) {}

    public function handle(DeleteSeriesCommand $command): void
    {
        if (! $this->authorizer->allows(ContentPermission::ManageTaxonomy->value)) {
            throw NotAuthorized::lacks(ContentPermission::ManageTaxonomy->value);
        }

        $id = SeriesId::fromInt($command->seriesId);

        if ($this->series->find($id) === null) {
            throw RecordNotFound::of('Series', $command->seriesId);
        }

        if ($this->series->hasArticles($id)) {
            throw InvalidInput::because('series', 'series_has_articles', 'The series still has articles.');
        }

        $this->series->delete($id);
    }
}
