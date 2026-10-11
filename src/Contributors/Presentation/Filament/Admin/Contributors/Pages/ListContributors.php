<?php

declare(strict_types=1);

namespace Elaf\Contributors\Presentation\Filament\Admin\Contributors\Pages;

use Elaf\Contributors\Presentation\Filament\Admin\Contributors\ContributorResource;
use Filament\Resources\Pages\ListRecords;

final class ListContributors extends ListRecords
{
    protected static string $resource = ContributorResource::class;
}
