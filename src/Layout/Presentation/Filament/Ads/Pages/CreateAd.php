<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Filament\Ads\Pages;

use Elaf\Layout\Application\Ad\CreateAd\CreateAdCommand;
use Elaf\Layout\Application\Ad\CreateAd\CreateAdHandler;
use Elaf\Layout\Infrastructure\Persistence\AdModel;
use Elaf\Layout\Presentation\Filament\Ads\AdResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateAd extends CreateRecord
{
    protected static string $resource = AdResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $id = UseCaseErrors::forForm(static fn () => app(CreateAdHandler::class)
            ->handle(new CreateAdCommand(FormData::actorId(), AdResource::input($data))));

        return AdModel::query()->findOrFail($id->value);
    }

    protected function getRedirectUrl(): string
    {
        return AdResource::getUrl('index');
    }
}
