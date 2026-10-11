<?php

declare(strict_types=1);

namespace Elaf\Contributors\Presentation\Filament\Auth;

use Elaf\Contributors\Application\UpdateProfile\UpdateContributorProfileCommand;
use Elaf\Contributors\Application\UpdateProfile\UpdateContributorProfileHandler;
use Elaf\Contributors\Infrastructure\Persistence\ContributorProfileModel;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Elaf\Shared\Presentation\PublicMedia;
use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * "My Profile" (FR-CON-05). A new display name waits for an editor.
 */
final class MyProfile extends EditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('contributors.fields.name'))
                ->required()
                ->maxLength(120)
                ->helperText(fn (): string => $this->pendingName() === null ? (string) __('contributors.profile.name_needs_approval')
                    : (string) __('contributors.profile.name_pending', ['name' => $this->pendingName()])),
            Textarea::make('bio')->label(__('contributors.fields.bio'))->required()->rows(4)->maxLength(1000),
            TextInput::make('phone')->label(__('contributors.fields.phone'))->tel()->maxLength(30),
            FileUpload::make('photo_path')
                ->label(__('contributors.fields.photo'))
                ->image()
                ->avatar()
                ->disk(PublicMedia::DISK)
                ->directory('contributors')
                ->maxSize(2048),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $profile = $this->profile();

        return [
            'name' => $profile->requested_name ?? ($data['name'] ?? ''),
            'bio' => $profile?->bio,
            'phone' => $profile?->phone,
            'photo_path' => $profile?->photo_path,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        UseCaseErrors::forForm(static fn () => app(UpdateContributorProfileHandler::class)->handle(new UpdateContributorProfileCommand(
            actorId: FormData::key($record),
            name: FormData::string($data, 'name'),
            bio: FormData::string($data, 'bio'),
            phone: FormData::text($data, 'phone'),
            photoPath: FormData::text($data, 'photo_path'),
        )));

        return $record->refresh();
    }

    private function pendingName(): ?string
    {
        return $this->profile()?->requested_name;
    }

    private function profile(): ?ContributorProfileModel
    {
        return ContributorProfileModel::query()->where('user_id', $this->getUser()->getKey())->first();
    }
}
