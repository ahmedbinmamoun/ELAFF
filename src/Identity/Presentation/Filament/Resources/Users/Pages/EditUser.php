<?php

declare(strict_types=1);

namespace Elaf\Identity\Presentation\Filament\Resources\Users\Pages;

use App\Models\User;
use Elaf\Identity\Application\StaffUser\UpdateStaffUser\UpdateStaffUserCommand;
use Elaf\Identity\Application\StaffUser\UpdateStaffUser\UpdateStaffUserHandler;
use Elaf\Identity\Presentation\Filament\Resources\Users\UserResource;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        return [
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'role' => $record instanceof User ? $record->getRoleNames()->first() : null,
            'password' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        UseCaseErrors::forForm(static fn () => app(UpdateStaffUserHandler::class)
            ->handle(new UpdateStaffUserCommand(FormData::actorId(), FormData::key($record), UserResource::input($data))));

        return $record->refresh();
    }
}
