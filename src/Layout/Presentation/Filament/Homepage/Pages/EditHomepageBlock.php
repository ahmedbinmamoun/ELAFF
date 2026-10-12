<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Filament\Homepage\Pages;

use Elaf\Layout\Application\Homepage\ConfigureBlock\ConfigureHomepageBlockCommand;
use Elaf\Layout\Application\Homepage\ConfigureBlock\ConfigureHomepageBlockHandler;
use Elaf\Layout\Infrastructure\Persistence\HomepageBlockModel;
use Elaf\Layout\Presentation\Filament\Homepage\HomepageBlockResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditHomepageBlock extends EditRecord
{
    protected static string $resource = HomepageBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [HomepageBlockResource::deleteAction()];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        return $record instanceof HomepageBlockModel ? [
            'type' => $record->type,
            'region' => $record->region,
            'is_active' => $record->is_active,
            'options' => is_array($record->config) ? $record->config : [],
        ] : $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $input = HomepageBlockResource::input([...$data, 'type' => $record instanceof HomepageBlockModel ? $record->type : '']);
        UseCaseErrors::forForm(static fn () => app(ConfigureHomepageBlockHandler::class)
            ->handle(new ConfigureHomepageBlockCommand(FormData::actorId(), FormData::key($record), $input)));

        return $record->refresh();
    }

    protected function getRedirectUrl(): string
    {
        return HomepageBlockResource::getUrl('index');
    }
}
