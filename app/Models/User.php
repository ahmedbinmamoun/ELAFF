<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Elaf\Identity\Application\Role;
use Elaf\Shared\Presentation\Filament\AdminPanelProvider;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Only newsroom staff reach /admin; contributors get their own panel (Phase 2b).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === AdminPanelProvider::ID
            && $this->hasAnyRole(array_map(static fn (Role $r): string => $r->value, Role::staff()));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
