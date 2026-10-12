<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Filament\Menus\Pages;

use Elaf\Layout\Application\Menu\MenuRepository;
use Elaf\Layout\Application\Menu\SaveMenu\SaveMenuCommand;
use Elaf\Layout\Application\Menu\SaveMenu\SaveMenuHandler;
use Elaf\Layout\Domain\Menu\MenuKey;
use Elaf\Layout\Infrastructure\Persistence\MenuModel;
use Elaf\Layout\Presentation\Filament\Menus\MenuResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditMenu extends EditRecord
{
    protected static string $resource = MenuResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $key = $record instanceof MenuModel ? MenuKey::tryFrom($record->key) : null;

        return ['items' => $key === null ? [] : MenuResource::toForm(app(MenuRepository::class)->items($key))];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        UseCaseErrors::forForm(static fn () => app(SaveMenuHandler::class)->handle(new SaveMenuCommand(
            FormData::actorId(),
            $record instanceof MenuModel ? $record->key : '',
            MenuResource::toInput(is_array($data['items'] ?? null) ? $data['items'] : []),
        )));

        return $record->refresh();
    }
}
