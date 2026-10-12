<?php

declare(strict_types=1);

namespace Elaf\Settings\Presentation\Filament\Pages\Pages;

use Elaf\Settings\Application\Page\CreatePage\CreatePageCommand;
use Elaf\Settings\Application\Page\CreatePage\CreatePageHandler;
use Elaf\Settings\Infrastructure\Persistence\PageModel;
use Elaf\Settings\Presentation\Filament\Pages\PageResource;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $id = UseCaseErrors::forForm(static fn () => app(CreatePageHandler::class)
            ->handle(new CreatePageCommand(PageResource::input($data))));

        return PageModel::query()->findOrFail($id->value);
    }
}
