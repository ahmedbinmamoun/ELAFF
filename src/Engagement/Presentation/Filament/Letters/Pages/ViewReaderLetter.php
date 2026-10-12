<?php

declare(strict_types=1);

namespace Elaf\Engagement\Presentation\Filament\Letters\Pages;

use Elaf\Engagement\Presentation\Filament\Letters\ReaderLetterResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewReaderLetter extends ViewRecord
{
    protected static string $resource = ReaderLetterResource::class;

    protected function getHeaderActions(): array
    {
        return ReaderLetterResource::moderationActions();
    }
}
