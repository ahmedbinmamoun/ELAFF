<?php

declare(strict_types=1);

namespace Elaf\Identity\Presentation\Filament\Resources\Users\Pages;

use App\Models\User;
use Elaf\Identity\Application\StaffUser\CreateStaffUser\CreateStaffUserCommand;
use Elaf\Identity\Application\StaffUser\CreateStaffUser\CreateStaffUserHandler;
use Elaf\Identity\Presentation\Filament\Resources\Users\UserResource;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $id = UseCaseErrors::forForm(static fn () => app(CreateStaffUserHandler::class)
            ->handle(new CreateStaffUserCommand(UserResource::input($data))));

        return User::query()->findOrFail($id->value);
    }
}
