<?php

declare(strict_types=1);

namespace Database\Seeders;

use Elaf\Content\Application\ContentPermission;
use Elaf\Identity\Application\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * The PRD §2 permission matrix. Idempotent: re-running syncs each role to
 * exactly this list. Later phases add their permissions here.
 */
final class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * @return array<string, list<ContentPermission>>
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
        ];
        $editorInChief = [...$editor, ContentPermission::PublishArticles, ContentPermission::PromoteArticles];

        return [
            Role::Writer->value => $writer,
            Role::Contributor->value => $writer,
            Role::Editor->value => $editor,
            Role::EditorInChief->value => $editorInChief,
            Role::Admin->value => ContentPermission::cases(),
        ];
    }

    public function run(PermissionRegistrar $registrar): void
    {
        $registrar->forgetCachedPermissions();

        foreach (ContentPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        foreach (self::matrix() as $role => $permissions) {
            RoleModel::findOrCreate($role, 'web')->syncPermissions(
                array_map(static fn (ContentPermission $p): string => $p->value, $permissions),
            );
        }

        $registrar->forgetCachedPermissions();
    }
}
