<?php

declare(strict_types=1);

namespace Elaf\Publishing\Presentation\Filament\Issues\Pages;

use Elaf\Publishing\Domain\Issue\ProcessingStatus;
use Elaf\Publishing\Infrastructure\Persistence\IssueModel;
use Elaf\Publishing\Presentation\Filament\Issues\IssueResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

final class ViewIssue extends ViewRecord
{
    protected static string $resource = IssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('public')
                ->label(__('admin.issue.view_public'))
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(static fn (IssueModel $record): string => route('issues.show', $record->number), shouldOpenInNewTab: true)
                ->visible(static fn (IssueModel $record): bool => $record->processing_status === ProcessingStatus::Ready->value),
            IssueResource::retryAction(),
            EditAction::make(),
        ];
    }
}
