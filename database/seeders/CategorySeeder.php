<?php

declare(strict_types=1);

namespace Database\Seeders;

use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Shared\Domain\Text\Slug;
use Illuminate\Database\Seeder;

/**
 * The paper's current sections (FR-TAX-01). Idempotent and non-destructive:
 * existing categories (matched by slug) are left as the editors changed them.
 */
final class CategorySeeder extends Seeder
{
    /**
     * Opinion sections are open to contributors by default (PRD §19 Q9 is
     * still open; editors can change this in the admin).
     */
    public const SECTIONS = [
        ['أخبار', false],
        ['أخبار الأسبوع', false],
        ['أسواق', false],
        ['تقارير', false],
        ['ما وراء الأخبار', false],
        ['تحقيق', false],
        ['حوار', false],
        ['رأي اقتصادي', true],
        ['رأي سياسي', true],
        ['من صحافة العالم', false],
        ['بريد القراء', false],
        ['أخيرة', false],
    ];

    public function run(): void
    {
        foreach (self::SECTIONS as $order => [$name, $openToContributors]) {
            CategoryModel::query()->firstOrCreate(
                ['slug' => Slug::fromText($name)->value],
                ['name' => $name, 'sort_order' => ($order + 1) * 10, 'is_open_to_contributors' => $openToContributors],
            );
        }
    }
}
