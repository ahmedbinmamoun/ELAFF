<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Safe to run in production and to re-run: every seeder is idempotent.
 * Demo content is added in the local environment only.
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            CategorySeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call(DemoContentSeeder::class);
        }
    }
}
