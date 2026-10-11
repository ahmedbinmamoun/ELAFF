<?php

declare(strict_types=1);

namespace Elaf\Contributors\Presentation\Filament\Admin\Contributors\Pages;

use Elaf\Contributors\Presentation\Filament\Admin\Contributors\ContributorResource;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;

final class ViewContributor extends ViewRecord
{
    protected static string $resource = ContributorResource::class;

    protected function getHeaderActions(): array
    {
        return [ActionGroup::make(ContributorResource::actions())->button()->label(__('contributors.admin.moderation'))];
    }
}
