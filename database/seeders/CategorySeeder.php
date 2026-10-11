<?php

declare(strict_types=1);

namespace Database\Seeders;

use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Shared\Domain\Text\Slug;
use Illuminate\Database\Seeder;

/**
 * The website's sections (FR-TAX-01, UI redesign). The printed paper's
 * sections are kept per article in print_section (config/elaf.php).
 * Idempotent and non-destructive: existing categories (matched by slug) keep
 * the editors' changes.
 */
final class CategorySeeder extends Seeder
{
    /**
     * name, icon key, open to contributors (opinion sections; PRD §19 Q9 open).
     */
    public const SECTIONS = [
        ['أخبار السودان', 'map', false],
        ['الاقتصاد', 'chart', false],
        ['النفط والطاقة', 'flame', false],
        ['السياسة', 'landmark', false],
        ['العالم', 'globe', false],
        ['تقارير وتحقيقات', 'document', false],
        ['آراء وكتابات', 'pen', true],
        ['مقالات', 'book', true],
        ['منوعات', 'grid', false],
    ];

    public function run(): void
    {
        foreach (self::SECTIONS as $order => [$name, $icon, $openToContributors]) {
            CategoryModel::query()->firstOrCreate(
                ['slug' => Slug::fromText($name)->value],
                ['name' => $name, 'icon' => $icon, 'sort_order' => ($order + 1) * 10, 'is_open_to_contributors' => $openToContributors],
            );
        }
    }
}
