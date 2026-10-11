<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Elaf\Contributors\Domain\ContributorStatus;
use Elaf\Contributors\Presentation\Filament\WriterPanelProvider;
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
 * @property string $password
 * @property string $type staff|contributor
 * @property string $status see ContributorStatus; staff are always "active"
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Staff reach /admin only; contributors reach /writer only, and not while
     * their email is unconfirmed, rejected or suspended (FR-CON-12, PRD §16).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            AdminPanelProvider::ID => $this->type === 'staff'
                && $this->hasAnyRole(array_map(static fn (Role $r): string => $r->value, Role::staff())),
            WriterPanelProvider::ID => $this->type === 'contributor'
                && ContributorStatus::tryFrom($this->status)?->canSignIn() === true,
            default => false,
        };
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
