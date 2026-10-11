<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Series\UpdateSeries;

use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Application\Series\SeriesInputValidator;
use Elaf\Content\Application\Series\SeriesRepository;
use Elaf\Content\Domain\Series\SeriesId;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\RecordNotFound;

final readonly class UpdateSeriesHandler
{
    public function __construct(
        private SeriesRepository $series,
        private SeriesInputValidator $validator,
        private Authorizer $authorizer,
    ) {}

    public function handle(UpdateSeriesCommand $command): void
    {
        if (! $this->authorizer->allows(ContentPermission::ManageTaxonomy->value)) {
            throw NotAuthorized::lacks(ContentPermission::ManageTaxonomy->value);
        }

        $id = SeriesId::fromInt($command->seriesId);

        if ($this->series->find($id) === null) {
            throw RecordNotFound::of('Series', $command->seriesId);
        }

        $this->series->update($id, $this->validator->validate($command->series, $id));
    }
}
