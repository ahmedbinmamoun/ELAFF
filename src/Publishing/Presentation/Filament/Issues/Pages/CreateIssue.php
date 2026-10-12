<?php

declare(strict_types=1);

namespace Elaf\Publishing\Presentation\Filament\Issues\Pages;

use Elaf\Publishing\Application\Upload\UploadIssueCommand;
use Elaf\Publishing\Application\Upload\UploadIssueHandler;
use Elaf\Publishing\Infrastructure\Persistence\IssueModel;
use Elaf\Publishing\Presentation\Filament\Issues\IssueResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateIssue extends CreateRecord
{
    protected static string $resource = IssueResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $id = UseCaseErrors::forForm(static fn () => app(UploadIssueHandler::class)->handle(new UploadIssueCommand(
            FormData::actorId(),
            IssueResource::input($data),
            IssueResource::uploadedPdf($data) ?? '',
        )));

        return IssueModel::query()->findOrFail($id->value);
    }

    protected function getCreatedNotificationTitle(): string
    {
        return (string) __('admin.issue.uploaded');
    }

    protected function getRedirectUrl(): string
    {
        return IssueResource::getUrl('index');
    }
}
