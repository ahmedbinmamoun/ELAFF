<?php

declare(strict_types=1);

namespace Elaf\Engagement\Presentation\Filament\Letters\Pages;

use Elaf\Engagement\Presentation\Filament\Letters\ReaderLetterResource;
use Filament\Resources\Pages\ListRecords;

final class ListReaderLetters extends ListRecords
{
    protected static string $resource = ReaderLetterResource::class;
}
