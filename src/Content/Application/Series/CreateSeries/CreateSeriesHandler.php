<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Series\CreateSeries;

use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Application\Series\SeriesInputValidator;
use Elaf\Content\Application\Series\SeriesRepository;
use Elaf\Content\Domain\Series\SeriesId;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;

final readonly class CreateSeriesHandler
{
    public function __construct(
        private SeriesRepository $series,
        private SeriesInputValidator $validator,
        private Authorizer $authorizer,
    ) {}

    public function handle(CreateSeriesCommand $command): SeriesId
    {
        if (! $this->authorizer->allows(ContentPermission::ManageTaxonomy->value)) {
            throw NotAuthorized::lacks(ContentPermission::ManageTaxonomy->value);
        }

        return $this->series->create($this->validator->validate($command->series));
    }
}
