<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Filament\Ads\Pages;

use Elaf\Layout\Application\Ad\UpdateAd\UpdateAdCommand;
use Elaf\Layout\Application\Ad\UpdateAd\UpdateAdHandler;
use Elaf\Layout\Infrastructure\Persistence\AdModel;
use Elaf\Layout\Presentation\Filament\Ads\AdResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditAd extends EditRecord
{
    protected static string $resource = AdResource::class;

    protected function getHeaderActions(): array
    {
        return [AdResource::deleteAction()];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        return [...$data, 'kind' => $record instanceof AdModel && $record->image_path === null ? 'html' : 'image'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        UseCaseErrors::forForm(static fn () => app(UpdateAdHandler::class)
            ->handle(new UpdateAdCommand(FormData::actorId(), FormData::key($record), AdResource::input($data))));

        return $record->refresh();
    }

    protected function getRedirectUrl(): string
    {
        return AdResource::getUrl('index');
    }
}
