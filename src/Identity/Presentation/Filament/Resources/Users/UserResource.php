<?php

declare(strict_types=1);

namespace Elaf\Identity\Presentation\Filament\Resources\Users;

use App\Models\User;
use BackedEnum;
use Elaf\Identity\Application\Role;
use Elaf\Identity\Application\StaffUser\StaffUserInput;
use Elaf\Identity\Presentation\Filament\Resources\Users\Pages\CreateUser;
use Elaf\Identity\Presentation\Filament\Resources\Users\Pages\EditUser;
use Elaf\Identity\Presentation\Filament\Resources\Users\Pages\ListUsers;
use Elaf\Shared\Presentation\Filament\FormData;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Newsroom accounts and their role. Contributors are managed in Phase 2b.
 *
 * @extends resource<User>
 */
final class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 90;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return (string) __('admin.user.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.user.plural');
    }

    public static function getNavigationGroup(): string
    {
        return (string) __('admin.navigation.administration');
    }

    /**
     * @return Builder<User>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('roles')
            ->whereHas('roles', static fn (Builder $q) => $q->whereIn('name', self::staffRoleNames()));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->label(self::label('name'))->required()->maxLength(120),
                TextInput::make('email')->label(self::label('email'))->email()->required()->maxLength(255),
                Select::make('role')
                    ->label(self::label('role'))
                    ->options(self::roleOptions())
                    ->required(),
                TextInput::make('password')
                    ->label(self::label('password'))
                    ->password()
                    ->revealable()
                    ->required(static fn (string $operation): bool => $operation === 'create')
                    ->helperText(static fn (string $operation): ?string => $operation === 'edit' ? (string) __('admin.user.fields.password_hint_edit') : null),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label(self::label('name'))->searchable()->sortable(),
                TextColumn::make('email')->label(self::label('email'))->searchable(),
                TextColumn::make('roles.name')
                    ->label(self::label('role'))
                    ->badge()
                    ->formatStateUsing(static fn (string $state): string => (string) __('admin.roles.'.$state)),
                TextColumn::make('created_at')->label(self::label('created_at'))->date('Y-m-d'),
            ])
            ->recordActions([EditAction::make()]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function input(array $data): StaffUserInput
    {
        return new StaffUserInput(
            name: FormData::string($data, 'name'),
            email: FormData::string($data, 'email'),
            role: FormData::string($data, 'role'),
            password: FormData::text($data, 'password'),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function roleOptions(): array
    {
        $options = [];
        foreach (Role::staff() as $role) {
            $options[$role->value] = (string) __('admin.roles.'.$role->value);
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    private static function staffRoleNames(): array
    {
        return array_map(static fn (Role $r): string => $r->value, Role::staff());
    }

    private static function label(string $field): string
    {
        return (string) __('admin.user.fields.'.$field);
    }
}
