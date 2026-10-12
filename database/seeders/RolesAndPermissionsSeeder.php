<?php

declare(strict_types=1);

namespace Database\Seeders;

use BackedEnum;
use Elaf\Content\Application\ContentPermission;
use Elaf\Contributors\Application\ContributorPermission;
use Elaf\Identity\Application\IdentityPermission;
use Elaf\Identity\Application\Role;
use Elaf\Publishing\Application\IssuePermission;
use Elaf\Settings\Application\SettingsPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;
use UnitEnum;

/**
 * The PRD §2 permission matrix. Idempotent: re-running syncs each role to
 * exactly this list. Later phases add their permissions here.
 */
final class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * @return list<BackedEnum&UnitEnum>
     */
    public static function permissions(): array
    {
        return [...ContentPermission::cases(), ...ContributorPermission::cases(), ...IdentityPermission::cases(), ...IssuePermission::cases(), ...SettingsPermission::cases()];
    }

    /**
     * @return array<string, list<BackedEnum>>
     */
    public static function matrix(): array
    {
        $writer = [ContentPermission::CreateArticles];
        $editor = [
            ...$writer,
            ContentPermission::EditAnyArticle,
            ContentPermission::ReviewArticles,
            ContentPermission::ManageTaxonomy,
            ContentPermission::ManageAuthors,
            ContributorPermission::ManageContributors,
        ];
        $editorInChief = [...$editor, ContentPermission::PublishArticles, ContentPermission::PromoteArticles, IssuePermission::ManageIssues, SettingsPermission::ManagePages];

        return [
            Role::Writer->value => $writer,
            Role::Contributor->value => $writer,
            Role::Editor->value => $editor,
            Role::EditorInChief->value => $editorInChief,
            Role::Admin->value => self::permissions(),
        ];
    }

    public function run(PermissionRegistrar $registrar): void
    {
        $registrar->forgetCachedPermissions();

        foreach (self::permissions() as $permission) {
            Permission::findOrCreate((string) $permission->value, 'web');
        }

        foreach (self::matrix() as $role => $permissions) {
            RoleModel::findOrCreate($role, 'web')->syncPermissions(
                array_map(static fn (BackedEnum $p): string => (string) $p->value, $permissions),
            );
        }

        $registrar->forgetCachedPermissions();
    }
}
