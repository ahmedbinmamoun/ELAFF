<?php

declare(strict_types=1);

namespace Elaf\Publishing\Presentation\Filament\Issues\Pages;

use Elaf\Publishing\Application\Update\UpdateIssueCommand;
use Elaf\Publishing\Application\Update\UpdateIssueHandler;
use Elaf\Publishing\Presentation\Filament\Issues\IssueResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditIssue extends EditRecord
{
    protected static string $resource = IssueResource::class;

    protected function getHeaderActions(): array
    {
        return [IssueResource::deleteAction()];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [...$data, 'pdf' => null];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        UseCaseErrors::forForm(static fn () => app(UpdateIssueHandler::class)->handle(new UpdateIssueCommand(
            FormData::actorId(),
            FormData::key($record),
            IssueResource::input($data),
            IssueResource::uploadedPdf($data),
        )));

        return $record->refresh();
    }

    protected function getRedirectUrl(): string
    {
        return IssueResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
